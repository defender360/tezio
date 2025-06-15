# ITSM Platform User Manual

## Table of Contents

1. [Getting Started](#getting-started)
2. [Dashboard](#dashboard)
3. [Incident Management](#incident-management)
4. [Change Management](#change-management)
5. [Problem Management](#problem-management)
6. [Service Requests](#service-requests)
7. [Knowledge Base](#knowledge-base)
8. [Reports and Analytics](#reports-and-analytics)
9. [User Profile](#user-profile)
10. [Tips and Best Practices](#tips-and-best-practices)

## Getting Started

### Logging In

1. Navigate to the ITSM Platform URL provided by your administrator
2. Click "Login" and enter your credentials
3. If using Single Sign-On (SSO), click "Login with SSO"
4. Complete any required multi-factor authentication

### First Time Setup

Upon first login, you'll be prompted to:
- Set your notification preferences
- Choose your default dashboard view
- Configure your time zone and display preferences

### Navigation

The main navigation menu is located on the left side of the screen:
- **Dashboard** - Overview and quick access
- **Incidents** - Manage IT incidents
- **Changes** - Handle change requests
- **Problems** - Track recurring issues
- **Service Catalog** - Request IT services
- **Knowledge Base** - Find solutions and documentation
- **Reports** - View analytics and metrics

## Dashboard

### Overview Dashboard

The main dashboard provides:
- **Key Metrics**: Open incidents, SLA compliance, pending approvals
- **Recent Activity**: Latest updates on your tickets
- **Quick Actions**: Create incident, search knowledge base
- **Charts**: Incident trends, workload distribution

### Customizing Your Dashboard

1. Click the "Customize" button in the top right
2. Drag and drop widgets to rearrange
3. Click "Add Widget" to include new components
4. Save your layout for future sessions

### Available Widgets

- **My Open Tickets**: Shows all tickets assigned to you
- **Team Queue**: Displays unassigned tickets for your team
- **SLA Status**: Monitors SLA compliance
- **Announcements**: Important system messages
- **Quick Links**: Frequently accessed items

## Incident Management

### Creating an Incident

1. Click "Create Incident" from the dashboard or navigation menu
2. Fill in the required fields:
   - **Title**: Brief description of the issue
   - **Description**: Detailed explanation of the problem
   - **Impact**: How many users/systems are affected
   - **Urgency**: How quickly resolution is needed
3. Add any relevant attachments (screenshots, logs)
4. Click "Submit"

### Incident Fields Explained

**Priority Calculation**:
- Critical = High Impact + High Urgency
- High = High Impact + Medium Urgency OR Medium Impact + High Urgency
- Medium = Medium Impact + Medium Urgency
- Low = Low Impact + Low Urgency

**Categories**:
- Hardware: Physical equipment issues
- Software: Application problems
- Network: Connectivity issues
- Access: Login and permission problems

### Working on Incidents

#### Accepting an Incident
1. Open the incident from your queue
2. Click "Accept" to assign it to yourself
3. Update the status to "In Progress"

#### Adding Updates
1. Use the comment section to document your investigation
2. Mark comments as "Internal" for team-only visibility
3. Attach relevant files or screenshots

#### Escalating an Incident
1. Click "Escalate" when additional expertise is needed
2. Select the target team or individual
3. Provide escalation notes
4. The incident remains visible in your queue for tracking

#### Resolving an Incident
1. Document the resolution in the "Resolution" field
2. Change status to "Resolved"
3. The reporter will be notified for confirmation
4. After confirmation or timeout, the incident auto-closes

### Using Templates

Common incident types have templates available:
1. Start typing in the title field
2. Select a suggested template
3. Fields will auto-populate with common values
4. Modify as needed for your specific case

## Change Management

### Change Types

**Standard Changes**: Pre-approved, low-risk changes
- Software updates
- User account modifications
- Routine maintenance

**Normal Changes**: Require CAB approval
- Infrastructure modifications
- Major software deployments
- Configuration changes

**Emergency Changes**: Expedited approval process
- Critical security patches
- Service restoration
- Urgent fixes

### Creating a Change Request

1. Navigate to Changes → Create Change
2. Select the change type
3. Complete the required information:
   - Implementation plan
   - Risk assessment
   - Rollback plan
   - Testing procedures
4. Set the implementation window
5. Submit for approval

### Change Advisory Board (CAB)

For normal changes:
1. The request goes to CAB review
2. CAB members evaluate risk and impact
3. You may be asked to present or clarify
4. Approval/rejection is communicated via email

### Implementing Changes

1. Wait for the approved implementation window
2. Update status to "Implementing"
3. Follow your documented plan
4. Document any deviations or issues
5. Complete post-implementation review
6. Close the change record

## Problem Management

### Identifying Problems

Problems are typically created when:
- Multiple similar incidents occur
- Root cause analysis is needed
- Preventive action is required

### Problem Investigation

1. Link related incidents to the problem
2. Document investigation findings
3. Identify root cause
4. Develop workaround if possible
5. Plan permanent fix

### Known Errors

When a problem's root cause is identified:
1. Update status to "Known Error"
2. Document the workaround
3. Link to knowledge articles
4. Plan the permanent resolution

## Service Requests

### Using the Service Catalog

1. Navigate to Service Catalog
2. Browse categories or use search
3. Click on the desired service
4. Fill out the request form
5. Review and submit

### Common Service Requests

**Hardware Requests**:
- New equipment
- Replacements
- Accessories

**Software Requests**:
- License procurement
- Installation assistance
- Access permissions

**Access Requests**:
- System access
- Elevated privileges
- VPN setup

### Tracking Requests

View your requests under "My Requests":
- See current status
- View approval progress
- Add comments or clarifications
- Cancel if needed

## Knowledge Base

### Searching for Solutions

1. Use the search bar in the Knowledge Base
2. Filter by category or tags
3. Sort by relevance or popularity
4. Check "Related Articles" section

### Article Features

- **Helpful/Not Helpful**: Rate articles to improve quality
- **Subscribe**: Get notified of updates
- **Share**: Send article link to colleagues
- **Print**: Generate printer-friendly version

### Contributing Knowledge

If you solve a unique issue:
1. Click "Contribute Article"
2. Use the template provided
3. Include step-by-step instructions
4. Add relevant screenshots
5. Submit for review

## Reports and Analytics

### Available Reports

**Operational Reports**:
- Open incidents by category
- SLA compliance
- Resolution times
- Workload distribution

**Trend Analysis**:
- Incident volume over time
- Recurring issues
- Peak hours analysis
- Category trends

**Performance Metrics**:
- First call resolution rate
- Average resolution time
- Customer satisfaction
- Team productivity

### Creating Custom Reports

1. Go to Reports → Custom Reports
2. Select data source (Incidents, Changes, etc.)
3. Choose fields and filters
4. Select visualization type
5. Save for future use

### Scheduling Reports

1. Open any report
2. Click "Schedule"
3. Set frequency (daily, weekly, monthly)
4. Choose recipients
5. Select format (PDF, Excel, CSV)

## User Profile

### Personal Settings

Access via your profile menu:
- **Profile Information**: Update contact details
- **Preferences**: Language, time zone, date format
- **Notifications**: Email, SMS, in-app settings
- **Security**: Password change, 2FA setup

### Notification Preferences

Configure how you receive alerts:
- **Email**: Immediate, digest, or disabled
- **SMS**: Critical alerts only
- **In-App**: Real-time notifications
- **Quiet Hours**: Set do-not-disturb times

### Out of Office

Set when you're unavailable:
1. Go to Profile → Out of Office
2. Set start and end dates
3. Designate a backup person
4. Tickets will auto-route to backup

## Tips and Best Practices

### Efficient Ticket Management

1. **Use Filters**: Save common filter combinations
2. **Keyboard Shortcuts**: 
   - `Ctrl+N`: New incident
   - `Ctrl+K`: Quick search
   - `Ctrl+S`: Save changes
3. **Bulk Actions**: Select multiple tickets for mass updates
4. **Quick Templates**: Create personal templates for common issues

### Communication Best Practices

1. **Be Specific**: Include error messages, timestamps
2. **Use Screenshots**: Annotate to highlight issues
3. **Update Regularly**: Keep stakeholders informed
4. **Professional Tone**: Remember all communications are logged

### Time Management

1. **Prioritize by SLA**: Focus on at-risk tickets first
2. **Use Categories**: Properly categorize for better routing
3. **Set Reminders**: For follow-ups and check-ins
4. **Delegate**: Don't hesitate to escalate when needed

### Knowledge Sharing

1. **Document Solutions**: Even simple fixes help others
2. **Update Articles**: Keep knowledge current
3. **Link Resources**: Connect incidents to knowledge articles
4. **Share Expertise**: Contribute to team knowledge

### Common Troubleshooting

**Can't Find a Ticket**:
- Check your filters
- Search by ticket number
- Look in "All Tickets" view

**Not Receiving Notifications**:
- Check notification preferences
- Verify email address
- Check spam folder
- Ensure not in quiet hours

**Access Denied**:
- Verify you're in the correct tenant
- Check with your administrator
- Ensure proper group membership

**Performance Issues**:
- Clear browser cache
- Try a different browser
- Check your internet connection
- Report to IT if persistent

## Appendix

### Glossary

- **CAB**: Change Advisory Board
- **CI**: Configuration Item
- **CMDB**: Configuration Management Database
- **KPI**: Key Performance Indicator
- **MTTR**: Mean Time To Resolution
- **RFC**: Request for Change
- **SLA**: Service Level Agreement
- **SLO**: Service Level Objective

### Keyboard Shortcuts

| Shortcut | Action |
|----------|---------|
| Ctrl+N | New incident |
| Ctrl+K | Quick search |
| Ctrl+S | Save |
| Ctrl+Enter | Submit form |
| Esc | Close modal |
| / | Focus search |
| G then I | Go to Incidents |
| G then C | Go to Changes |
| G then K | Go to Knowledge Base |

### Contact Support

**Internal Support**:
- Help Desk: ext. 1234
- Email: itsupport@company.com
- Portal: Create a ticket

**Platform Support**:
- Documentation: https://docs.itsm-platform.com
- Community: https://community.itsm-platform.com
- Email: support@itsm-platform.com