import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/services/api'

export interface Team {
  id: string
  name: string
  description?: string
  manager_id?: string
  member_count: number
  active: boolean
  created_at: string
  updated_at: string
}

export interface TeamMember {
  id: string
  name: string
  email: string
  role: string
  team_id: string
  skills: string[]
  active: boolean
}

export const useTeamStore = defineStore('team', () => {
  // State
  const teams = ref<Team[]>([])
  const currentTeam = ref<Team | null>(null)
  const teamMembers = ref<TeamMember[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Actions
  const getTeams = async () => {
    loading.value = true
    error.value = null
    try {
      // Mock data for now - replace with actual API call
      teams.value = [
        {
          id: '1',
          name: 'Service Desk Team',
          description: 'First-line support team',
          manager_id: '1',
          member_count: 12,
          active: true,
          created_at: '2024-01-01',
          updated_at: '2024-01-14'
        },
        {
          id: '2',
          name: 'Infrastructure Team',
          description: 'Server and network support',
          manager_id: '2',
          member_count: 8,
          active: true,
          created_at: '2024-01-01',
          updated_at: '2024-01-14'
        },
        {
          id: '3',
          name: 'Application Support',
          description: 'Application maintenance and support',
          manager_id: '3',
          member_count: 10,
          active: true,
          created_at: '2024-01-01',
          updated_at: '2024-01-14'
        },
        {
          id: '4',
          name: 'Security Team',
          description: 'Security operations and incident response',
          manager_id: '4',
          member_count: 6,
          active: true,
          created_at: '2024-01-01',
          updated_at: '2024-01-14'
        }
      ]
      return teams.value
    } catch (err: any) {
      error.value = err.message || 'Failed to load teams'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getTeam = async (id: string) => {
    loading.value = true
    error.value = null
    try {
      // In real implementation, this would be an API call
      const team = teams.value.find(t => t.id === id)
      if (team) {
        currentTeam.value = team
        return team
      } else {
        throw new Error('Team not found')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to load team'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getTeamMembers = async (teamId: string) => {
    loading.value = true
    error.value = null
    try {
      // Mock data for now - replace with actual API call
      teamMembers.value = [
        {
          id: '1',
          name: 'John Doe',
          email: 'john.doe@example.com',
          role: 'Senior Support Engineer',
          team_id: teamId,
          skills: ['Windows', 'Linux', 'Networking'],
          active: true
        },
        {
          id: '2',
          name: 'Jane Smith',
          email: 'jane.smith@example.com',
          role: 'Support Engineer',
          team_id: teamId,
          skills: ['Windows', 'Office 365', 'Azure'],
          active: true
        },
        {
          id: '3',
          name: 'Mike Johnson',
          email: 'mike.johnson@example.com',
          role: 'Junior Support Engineer',
          team_id: teamId,
          skills: ['Windows', 'Basic Networking'],
          active: true
        }
      ]
      return teamMembers.value
    } catch (err: any) {
      error.value = err.message || 'Failed to load team members'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createTeam = async (data: Partial<Team>) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/teams', data)
      const newTeam = response.data
      teams.value.push(newTeam)
      return newTeam
    } catch (err: any) {
      error.value = err.message || 'Failed to create team'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateTeam = async (id: string, data: Partial<Team>) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.patch(`/teams/${id}`, data)
      const updatedTeam = response.data
      const index = teams.value.findIndex(t => t.id === id)
      if (index !== -1) {
        teams.value[index] = updatedTeam
      }
      if (currentTeam.value?.id === id) {
        currentTeam.value = updatedTeam
      }
      return updatedTeam
    } catch (err: any) {
      error.value = err.message || 'Failed to update team'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteTeam = async (id: string) => {
    loading.value = true
    error.value = null
    try {
      await api.delete(`/teams/${id}`)
      teams.value = teams.value.filter(t => t.id !== id)
      if (currentTeam.value?.id === id) {
        currentTeam.value = null
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to delete team'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    teams,
    currentTeam,
    teamMembers,
    loading,
    error,
    
    // Actions
    getTeams,
    getTeam,
    getTeamMembers,
    createTeam,
    updateTeam,
    deleteTeam
  }
})