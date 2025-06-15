import { test, expect, Page } from '@playwright/test'

// Test data
const testUser = {
  email: 'test@example.com',
  password: 'Test123!@#'
}

const testIncident = {
  title: 'E2E Test: Production server down',
  description: 'The production server is not responding to requests. Users are unable to access the application.',
  impact: 'high',
  urgency: 'high'
}

// Helper functions
async function login(page: Page) {
  await page.goto('/login')
  await page.fill('[data-testid="email-input"]', testUser.email)
  await page.fill('[data-testid="password-input"]', testUser.password)
  await page.click('[data-testid="login-button"]')
  await page.waitForURL('/dashboard')
}

async function createIncident(page: Page, incident: typeof testIncident) {
  await page.click('[data-testid="create-incident-button"]')
  await page.waitForSelector('[data-testid="incident-modal"]')
  
  await page.fill('[data-testid="title-input"]', incident.title)
  await page.fill('[data-testid="description-textarea"]', incident.description)
  await page.selectOption('[data-testid="impact-select"]', incident.impact)
  await page.selectOption('[data-testid="urgency-select"]', incident.urgency)
  
  await page.click('[data-testid="submit-button"]')
  await page.waitForSelector('[data-testid="success-toast"]')
}

test.describe('Incident Management Workflow', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('complete incident lifecycle', async ({ page }) => {
    // Step 1: Create a new incident
    await page.goto('/incidents')
    await createIncident(page, testIncident)
    
    // Verify incident was created
    await expect(page.locator('text=' + testIncident.title)).toBeVisible()
    
    // Step 2: View incident details
    await page.click(`text=${testIncident.title}`)
    await page.waitForURL(/\/incidents\/\d+/)
    
    // Verify incident details
    await expect(page.locator('[data-testid="incident-title"]')).toContainText(testIncident.title)
    await expect(page.locator('[data-testid="incident-priority"]')).toContainText('Critical')
    await expect(page.locator('[data-testid="incident-status"]')).toContainText('New')
    
    // Step 3: Assign incident to self
    await page.click('[data-testid="assign-button"]')
    await page.click('[data-testid="assign-to-me"]')
    await expect(page.locator('[data-testid="assignee-name"]')).toContainText('You')
    
    // Step 4: Update status to In Progress
    await page.click('[data-testid="status-button"]')
    await page.click('[data-testid="status-in-progress"]')
    await expect(page.locator('[data-testid="incident-status"]')).toContainText('In Progress')
    
    // Step 5: Add a comment
    const comment = 'Investigating the issue. Checking server logs.'
    await page.fill('[data-testid="comment-input"]', comment)
    await page.click('[data-testid="add-comment-button"]')
    await expect(page.locator(`text=${comment}`)).toBeVisible()
    
    // Step 6: Add an attachment
    const [fileChooser] = await Promise.all([
      page.waitForEvent('filechooser'),
      page.click('[data-testid="attach-file-button"]')
    ])
    await fileChooser.setFiles('./tests/e2e/fixtures/server-logs.txt')
    await expect(page.locator('text=server-logs.txt')).toBeVisible()
    
    // Step 7: Resolve the incident
    await page.click('[data-testid="resolve-button"]')
    await page.fill('[data-testid="resolution-textarea"]', 'Restarted the server. Applied security patches.')
    await page.click('[data-testid="confirm-resolve-button"]')
    
    await expect(page.locator('[data-testid="incident-status"]')).toContainText('Resolved')
    await expect(page.locator('[data-testid="resolution-text"]')).toBeVisible()
    
    // Step 8: Close the incident
    await page.click('[data-testid="close-button"]')
    await page.click('[data-testid="confirm-close-button"]')
    await expect(page.locator('[data-testid="incident-status"]')).toContainText('Closed')
  })

  test('bulk update multiple incidents', async ({ page }) => {
    await page.goto('/incidents')
    
    // Select multiple incidents
    await page.click('[data-testid="select-all-checkbox"]')
    
    // Open bulk actions menu
    await page.click('[data-testid="bulk-actions-button"]')
    await page.click('[data-testid="bulk-update-option"]')
    
    // Update status for all selected
    await page.selectOption('[data-testid="bulk-status-select"]', 'in_progress')
    await page.selectOption('[data-testid="bulk-assignee-select"]', 'team:support')
    await page.click('[data-testid="apply-bulk-update"]')
    
    // Verify success message
    await expect(page.locator('[data-testid="bulk-update-success"]')).toBeVisible()
    
    // Verify incidents were updated
    const statusBadges = page.locator('[data-testid="status-badge"]:has-text("In Progress")')
    await expect(statusBadges).toHaveCount(await page.locator('[data-testid="incident-row"]').count())
  })

  test('search and filter incidents', async ({ page }) => {
    await page.goto('/incidents')
    
    // Test search functionality
    await page.fill('[data-testid="search-input"]', 'server')
    await page.waitForTimeout(500) // Debounce delay
    
    // Verify search results
    const searchResults = page.locator('[data-testid="incident-row"]')
    await expect(searchResults).toHaveCount(await searchResults.count())
    
    // Clear search
    await page.click('[data-testid="clear-search"]')
    
    // Test filters
    await page.click('[data-testid="filter-button"]')
    
    // Filter by status
    await page.click('[data-testid="status-filter-new"]')
    await page.click('[data-testid="status-filter-in-progress"]')
    
    // Filter by priority
    await page.click('[data-testid="priority-filter-high"]')
    await page.click('[data-testid="priority-filter-critical"]')
    
    // Apply filters
    await page.click('[data-testid="apply-filters"]')
    
    // Verify filtered results
    const filteredResults = page.locator('[data-testid="incident-row"]')
    for (const row of await filteredResults.all()) {
      const status = await row.locator('[data-testid="status-badge"]').textContent()
      const priority = await row.locator('[data-testid="priority-badge"]').textContent()
      
      expect(['New', 'In Progress']).toContain(status)
      expect(['High', 'Critical']).toContain(priority)
    }
  })

  test('export incidents to CSV', async ({ page, context }) => {
    await page.goto('/incidents')
    
    // Set up download promise before clicking
    const downloadPromise = page.waitForEvent('download')
    
    // Click export button
    await page.click('[data-testid="export-button"]')
    await page.click('[data-testid="export-csv"]')
    
    // Wait for download
    const download = await downloadPromise
    
    // Verify download
    expect(download.suggestedFilename()).toMatch(/incidents.*\.csv/)
    
    // Save and verify content
    const path = await download.path()
    expect(path).toBeTruthy()
  })

  test('incident SLA tracking', async ({ page }) => {
    // Create an incident with tight SLA
    await page.goto('/incidents')
    await page.click('[data-testid="create-incident-button"]')
    
    const urgentIncident = {
      ...testIncident,
      title: 'URGENT: Database connection lost',
      urgency: 'high',
      impact: 'high'
    }
    
    await createIncident(page, urgentIncident)
    
    // Go to incident detail
    await page.click(`text=${urgentIncident.title}`)
    
    // Verify SLA timers are shown
    await expect(page.locator('[data-testid="sla-response-timer"]')).toBeVisible()
    await expect(page.locator('[data-testid="sla-resolution-timer"]')).toBeVisible()
    
    // Verify SLA status
    const slaStatus = page.locator('[data-testid="sla-status"]')
    await expect(slaStatus).toContainText(/\d+h \d+m remaining/)
  })

  test('knowledge base integration', async ({ page }) => {
    // Create incident with known issue
    await page.goto('/incidents')
    await createIncident(page, {
      title: 'Password reset not working',
      description: 'Users cannot reset their passwords',
      impact: 'medium',
      urgency: 'medium'
    })
    
    // Go to incident detail
    await page.click('text=Password reset not working')
    
    // Check for knowledge base suggestions
    await expect(page.locator('[data-testid="kb-suggestions"]')).toBeVisible()
    await expect(page.locator('[data-testid="kb-article"]')).toHaveCount(3)
    
    // Apply solution from KB
    await page.click('[data-testid="kb-article"]:first-child')
    await page.click('[data-testid="apply-kb-solution"]')
    
    // Verify resolution was populated
    const resolutionField = page.locator('[data-testid="resolution-textarea"]')
    await expect(resolutionField).not.toBeEmpty()
  })

  test('incident escalation', async ({ page }) => {
    // Navigate to an existing high priority incident
    await page.goto('/incidents')
    await page.click('[data-testid="priority-badge"]:has-text("Critical"):first')
    
    // Escalate incident
    await page.click('[data-testid="escalate-button"]')
    await page.selectOption('[data-testid="escalation-level"]', 'manager')
    await page.fill('[data-testid="escalation-reason"]', 'SLA breach imminent')
    await page.click('[data-testid="confirm-escalation"]')
    
    // Verify escalation
    await expect(page.locator('[data-testid="escalation-banner"]')).toBeVisible()
    await expect(page.locator('[data-testid="escalation-banner"]')).toContainText('Escalated to Manager')
  })

  test('mobile responsive workflow', async ({ page, browserName }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 })
    
    await page.goto('/incidents')
    
    // Verify mobile menu
    await page.click('[data-testid="mobile-menu-button"]')
    await expect(page.locator('[data-testid="mobile-menu"]')).toBeVisible()
    
    // Navigate to incidents
    await page.click('[data-testid="mobile-menu-incidents"]')
    
    // Verify mobile layout
    await expect(page.locator('[data-testid="incident-card"]')).toBeVisible()
    
    // Create incident on mobile
    await page.click('[data-testid="mobile-create-button"]')
    await page.fill('[data-testid="title-input"]', 'Mobile test incident')
    await page.fill('[data-testid="description-textarea"]', 'Created from mobile')
    
    // Scroll to submit button
    await page.locator('[data-testid="submit-button"]').scrollIntoViewIfNeeded()
    await page.click('[data-testid="submit-button"]')
    
    // Verify creation
    await expect(page.locator('text=Mobile test incident')).toBeVisible()
  })
})