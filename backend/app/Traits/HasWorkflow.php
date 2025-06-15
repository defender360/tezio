<?php

namespace App\Traits;

use App\Models\WorkflowTransition;
use App\Models\WorkflowState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait HasWorkflow
{
    /**
     * Boot the trait
     */
    public static function bootHasWorkflow()
    {
        static::creating(function ($model) {
            if (empty($model->workflow_state)) {
                $model->workflow_state = $model->getInitialWorkflowState();
            }
        });
    }

    /**
     * Get the initial workflow state
     */
    public function getInitialWorkflowState(): string
    {
        return 'draft';
    }

    /**
     * Get available transitions from current state
     */
    public function getAvailableTransitions(): array
    {
        $currentState = $this->workflow_state;
        $modelType = class_basename($this);
        
        return WorkflowTransition::where('model_type', $modelType)
            ->where('from_state', $currentState)
            ->where('is_active', true)
            ->get()
            ->filter(function ($transition) {
                return $this->canTransition($transition);
            })
            ->pluck('to_state', 'name')
            ->toArray();
    }

    /**
     * Check if transition is allowed
     */
    public function canTransition(WorkflowTransition|string $transition): bool
    {
        if (is_string($transition)) {
            $transition = WorkflowTransition::where('name', $transition)
                ->where('model_type', class_basename($this))
                ->where('from_state', $this->workflow_state)
                ->first();
        }

        if (!$transition) {
            return false;
        }

        // Check conditions
        if ($transition->conditions) {
            foreach ($transition->conditions as $condition) {
                if (!$this->evaluateCondition($condition)) {
                    return false;
                }
            }
        }

        // Check permissions
        if ($transition->required_permission && auth()->check()) {
            return auth()->user()->can($transition->required_permission);
        }

        return true;
    }

    /**
     * Transition to a new state
     */
    public function transitionTo(string $transitionName, array $data = []): bool
    {
        $transition = WorkflowTransition::where('name', $transitionName)
            ->where('model_type', class_basename($this))
            ->where('from_state', $this->workflow_state)
            ->first();

        if (!$transition || !$this->canTransition($transition)) {
            return false;
        }

        DB::beginTransaction();
        try {
            // Execute pre-transition actions
            $this->executeTransitionActions($transition->pre_actions, $data);

            // Update state
            $oldState = $this->workflow_state;
            $this->workflow_state = $transition->to_state;
            $this->save();

            // Log transition
            $this->logWorkflowTransition($oldState, $transition->to_state, $transitionName, $data);

            // Execute post-transition actions
            $this->executeTransitionActions($transition->post_actions, $data);

            // Fire events
            $this->fireWorkflowEvents($transition, $oldState, $data);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Workflow transition failed', [
                'model' => class_basename($this),
                'id' => $this->id,
                'transition' => $transitionName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Evaluate workflow condition
     */
    protected function evaluateCondition(array $condition): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? null;

        if (!$field) {
            return true;
        }

        $fieldValue = data_get($this, $field);

        return match($operator) {
            '=' => $fieldValue == $value,
            '!=' => $fieldValue != $value,
            '>' => $fieldValue > $value,
            '<' => $fieldValue < $value,
            '>=' => $fieldValue >= $value,
            '<=' => $fieldValue <= $value,
            'in' => in_array($fieldValue, (array)$value),
            'not_in' => !in_array($fieldValue, (array)$value),
            'null' => is_null($fieldValue),
            'not_null' => !is_null($fieldValue),
            default => false
        };
    }

    /**
     * Execute transition actions
     */
    protected function executeTransitionActions(?array $actions, array $data): void
    {
        if (!$actions) {
            return;
        }

        foreach ($actions as $action) {
            $this->executeAction($action, $data);
        }
    }

    /**
     * Execute a single action
     */
    protected function executeAction(array $action, array $data): void
    {
        $type = $action['type'] ?? null;
        $params = $action['params'] ?? [];

        switch ($type) {
            case 'update_field':
                $this->update([
                    $params['field'] => $params['value'] ?? null
                ]);
                break;
                
            case 'send_notification':
                // Implement notification sending
                break;
                
            case 'call_method':
                if (method_exists($this, $params['method'])) {
                    $this->{$params['method']}($data);
                }
                break;
                
            case 'dispatch_job':
                if (class_exists($params['job'])) {
                    dispatch(new $params['job']($this, $data));
                }
                break;
        }
    }

    /**
     * Log workflow transition
     */
    protected function logWorkflowTransition(string $fromState, string $toState, string $transition, array $data): void
    {
        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties([
                'from_state' => $fromState,
                'to_state' => $toState,
                'transition' => $transition,
                'data' => $data
            ])
            ->log("Workflow transition: {$fromState} -> {$toState}");
    }

    /**
     * Fire workflow events
     */
    protected function fireWorkflowEvents(WorkflowTransition $transition, string $oldState, array $data): void
    {
        $eventClass = "App\\Events\\" . class_basename($this) . "StateChanged";
        if (class_exists($eventClass)) {
            event(new $eventClass($this, $oldState, $transition->to_state, $data));
        }

        if ($transition->fire_event && class_exists($transition->fire_event)) {
            event(new $transition->fire_event($this, $data));
        }
    }

    /**
     * Get workflow history
     */
    public function getWorkflowHistory()
    {
        return $this->activities()
            ->where('description', 'like', 'Workflow transition:%')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Check if in specific state
     */
    public function isInState(string|array $states): bool
    {
        if (is_array($states)) {
            return in_array($this->workflow_state, $states);
        }
        
        return $this->workflow_state === $states;
    }

    /**
     * Get state label
     */
    public function getStateLabel(): string
    {
        $state = WorkflowState::where('model_type', class_basename($this))
            ->where('name', $this->workflow_state)
            ->first();
            
        return $state?->label ?? str_replace('_', ' ', ucfirst($this->workflow_state));
    }

    /**
     * Get state color
     */
    public function getStateColor(): string
    {
        $state = WorkflowState::where('model_type', class_basename($this))
            ->where('name', $this->workflow_state)
            ->first();
            
        return $state?->color ?? 'gray';
    }
}