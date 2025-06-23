# MCP Setup Guide - Tezio ITSM Platform

## Overview

This guide explains how to set up Model Context Protocol (MCP) servers for the Tezio ITSM Platform development workflow using Claude Code.

## Quick Setup

1. **Copy the MCP configuration template:**
   ```bash
   cp .claude/mcp.json.example .claude/mcp.json
   ```

2. **Update the configuration with your credentials:**
   - Replace `YOUR_GITHUB_TOKEN_HERE` with your GitHub Personal Access Token
   - Replace `YOUR_BRAVE_API_KEY_HERE` with your Brave Search API key
   - Update the filesystem path to your project directory

3. **Start Claude Code with MCP support:**
   ```bash
   claude-code --mcp-config .claude/mcp.json
   ```

## Available MCP Servers

### 🐙 GitHub Integration
- **Purpose:** Repository management, file updates, PR creation
- **Setup:** Requires GitHub Personal Access Token with repository permissions
- **Usage:** Push code changes directly from Claude Code

### 🔍 Brave Search
- **Purpose:** Web search for documentation and troubleshooting
- **Setup:** Requires Brave Search API key
- **Usage:** Search for technical documentation and solutions

### 📁 Filesystem Access
- **Purpose:** Read/write project files
- **Setup:** Configure path to your Tezio project directory
- **Usage:** Direct file manipulation and code generation

### 🐘 PostgreSQL Connection
- **Purpose:** Database queries and data analysis
- **Setup:** Uses project's PostgreSQL credentials
- **Usage:** Direct database access for debugging and data inspection

### 🤖 Browser Automation
- **Purpose:** Testing and UI automation
- **Setup:** No additional configuration required
- **Usage:** Automated testing of the web interface

### 🧠 Memory Storage
- **Purpose:** Session data and context preservation
- **Setup:** No additional configuration required
- **Usage:** Maintains context across Claude Code sessions

## Configuration Details

### GitHub Token Permissions
Your GitHub Personal Access Token should have the following scopes:
- `repo` - Full repository access
- `workflow` - Update GitHub Actions workflows
- `read:user` - Read user profile information

### Database Connection
The PostgreSQL connection uses the same credentials as your Docker setup:
- **Host:** localhost:5432
- **Database:** itsm_platform
- **Username:** itsm_user
- **Password:** secure_password_here

### Security Notes

⚠️ **Important Security Considerations:**
- Never commit `.claude/mcp.json` with real credentials to version control
- Use environment variables for sensitive information in production
- Regularly rotate API keys and tokens
- The example file `.claude/mcp.json.example` is safe to commit

## Usage Examples

### Updating Documentation
```bash
# Claude Code can now directly update files via GitHub MCP
"Update the README.md file with the latest installation instructions"
```

### Database Queries
```bash
# Direct PostgreSQL access for debugging
"Show me all incidents created in the last 24 hours"
```

### Code Search and Updates
```bash
# Search and modify code across the project
"Find all references to the old authentication method and update them"
```

### Web Research
```bash
# Use Brave Search for external documentation
"Search for Laravel Sanctum best practices for multi-tenant applications"
```

## Troubleshooting

### Connection Issues
1. **GitHub API Rate Limits:** GitHub has API rate limits. If you encounter limits, wait or use a different token.
2. **Database Connection:** Ensure your Docker containers are running: `docker-compose ps`
3. **File Permissions:** Make sure Claude Code has read/write access to your project directory.

### MCP Server Errors
1. **Install Dependencies:** Ensure Node.js packages are available: `npm install -g @modelcontextprotocol/server-*`
2. **Check Logs:** Use Claude Code debug mode to see MCP server logs
3. **Restart Servers:** Restart Claude Code to reinitialize MCP connections

## Advanced Configuration

### Custom Server Endpoints
You can add custom MCP servers for specific project needs:

```json
{
  "mcpServers": {
    "custom-api": {
      "command": "node",
      "args": ["./scripts/custom-mcp-server.js"],
      "env": {
        "API_ENDPOINT": "https://api.example.com"
      },
      "description": "Custom API integration"
    }
  }
}
```

### Environment-Specific Configs
Create different MCP configurations for different environments:
- `.claude/mcp.json` - Local development
- `.claude/mcp.staging.json` - Staging environment
- `.claude/mcp.production.json` - Production (with restricted permissions)

## Integration with Tezio Platform

The MCP configuration is specifically tailored for the Tezio ITSM Platform:

1. **Database Schema Awareness:** Direct access to the PostgreSQL schema with 150+ tables
2. **Docker Integration:** Filesystem access works with Docker volume mounts
3. **Laravel Integration:** Understands Laravel project structure and conventions
4. **Vue.js Frontend:** Can manipulate frontend components and configurations

## Next Steps

1. **Set up your MCP configuration** following this guide
2. **Test the connections** by asking Claude Code to perform simple tasks
3. **Explore the capabilities** by requesting code updates, database queries, and documentation searches
4. **Customize further** by adding project-specific MCP servers

---

**Related Documentation:**
- [Claude.md](../claude.md) - Main project reference
- [Architecture Documentation](../ARCHITECTURE_EXPLAINED.md) - System architecture
- [Development Guide](../docs/DEVELOPMENT_GUIDE.md) - Development workflow

**Last Updated:** December 2024