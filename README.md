# Elementor Git Publisher

🚀 **Revolutionary WordPress Plugin** - Publish Elementor pages through Git workflow with automatic pull requests, version control, and team collaboration.

Transform your Elementor publishing workflow into a professional Git-based review system. No more accidental overwrites, no more lost changes, and complete visibility into every page modification.

## ✨ Features

- **🔄 Git Workflow Integration** - Every Elementor save creates a GitHub pull request
- **📋 Version Control** - Complete history of all page changes stored in Git
- **👥 Team Collaboration** - Review changes before they go live
- **🔍 Advanced Debugging** - Comprehensive logging system for troubleshooting
- **⚡ Seamless Integration** - Works transparently with existing Elementor workflow
- **🛡️ Safe Publishing** - No more accidental overwrites or lost work
- **📊 Admin Dashboard** - Monitor all Git operations and pending reviews

## 🎯 How It Works

1. **Enable** Git workflow for any Elementor page
2. **Edit** your page in Elementor as usual
3. **Save** - automatically creates GitHub branch + pull request
4. **Review** changes on GitHub with your team
5. **Merge** PR to publish changes live
6. **Track** complete version history forever

## 📦 Installation

### Prerequisites
- WordPress 5.0+
- Elementor Plugin (free or pro)
- GitHub account
- GitHub repository for storing page versions

### Plugin Installation

1. **Download** the plugin files
2. **Upload** to `/wp-content/plugins/elementor-git-publisher/`
3. **Activate** the plugin through WordPress admin
4. **Configure** GitHub settings (see Configuration section)

## ⚙️ Configuration

### 1. GitHub Setup

1. **Create Repository**: Create a new GitHub repository for your page backups
2. **Generate Token**:
    - Go to GitHub Settings → Developer settings → Personal access tokens
    - Create token with `repo` permissions
    - Copy the token (you won't see it again!)

### 2. Plugin Configuration

1. Navigate to **Settings → Git Publisher**
2. Enter your **GitHub Token**
3. Enter your **Repository** (format: `username/repo-name`)
4. **Enable Git Publishing**
5. **Test Connection** to verify setup

### 3. Page Setup

1. **Edit any page** in WordPress admin
2. Find **"Git Publisher"** meta box in sidebar
3. **Check** "Enable Git workflow for this page"
4. **Save** the page
5. Now Elementor saves will create GitHub PRs! 🎉

## 🔧 Usage

### Basic Workflow
```
Edit Page in Elementor → Save → GitHub PR Created → Review → Merge → Live!
```

### Per-Page Control
- Enable/disable Git workflow for individual pages
- Some pages can use normal publishing, others use Git workflow
- Perfect for mixing content types and workflows

### Monitoring & Debugging
- **Settings → Git Publisher → Debug Logs** tab
- View all Git operations and their status
- System status checker
- Test workflow functionality
- Clear logs and troubleshoot issues

## 🐛 Troubleshooting

### Common Issues

**No PR Created When Saving:**
1. Check Settings → Git Publisher → Debug Logs tab
2. Verify GitHub token has `repo` permissions
3. Ensure repository exists and is accessible
4. Check that page has Git workflow enabled

**GitHub Connection Failed:**
1. Verify token is correct (regenerate if needed)
2. Check repository format: `username/repo-name`
3. Ensure repository exists and isn't private (unless token has access)

**Elementor Not Detected:**
1. Ensure Elementor plugin is installed and activated
2. Page must be built with Elementor (not classic editor)

## 📋 File Structure

```
elementor-git-publisher/
├── elementor-git-publisher.php          # Main plugin file
├── includes/
│   ├── class-admin.php                  # Admin interface & settings
│   ├── class-logger.php                 # Debugging & logging system
│   ├── class-github-manager.php         # GitHub API integration
│   └── class-elementor-hooks.php        # Elementor save interceptor
└── README.md                            # This file
```

## 🚀 Changelog

### Version 1.0.0 - Foundation Release

#### Phase 1: Core Infrastructure ✅
- **Plugin Foundation**: Safe, crash-proof plugin architecture
- **GitHub Integration**: Complete GitHub API wrapper with error handling
- **Settings System**: Professional admin interface with connection testing
- **Safety First**: Bulletproof initialization and dependency checking

#### Phase 2: Elementor Integration ✅
- **Save Interception**: Hook into Elementor's save process seamlessly
- **Page Data Serialization**: Capture and format all Elementor page data
- **Branch & PR Creation**: Automatic GitHub branch and pull request generation
- **Meta Box Controls**: Per-page Git workflow enable/disable functionality

#### Phase 3: Professional Debugging ✅
- **Comprehensive Logging**: Track every operation with detailed context
- **Debug Dashboard**: Professional tabbed admin interface
- **System Status**: Real-time configuration and health monitoring
- **Console Integration**: Browser console logging for developers
- **Error Tracking**: WordPress error log integration
- **Test Workflows**: Manual testing tools for troubleshooting

#### Technical Achievements ✅
- **Zero Crashes**: Fault-tolerant error handling throughout
- **Modular Architecture**: Clean, maintainable code structure
- **Security**: Proper nonces, capability checks, and sanitization
- **Performance**: Efficient API calls and minimal overhead
- **User Experience**: Intuitive interface with helpful guidance

## 🛣️ Roadmap

### Phase 4: Enhanced Workflow (Coming Next)
- **Webhook Integration**: Auto-publish when PR is merged
- **Preview System**: Staging URLs for reviewing changes
- **Rollback Support**: One-click rollback to previous versions

### Phase 5: Team Features (Planned)
- **User Permissions**: Role-based workflow controls
- **Notification System**: Slack/email notifications for PR events
- **Bulk Operations**: Handle multiple pages at once

### Phase 6: Enterprise Features (Future)
- **White Label**: Customize branding for agencies
- **Client Portals**: Give clients review access without GitHub
- **Advanced Analytics**: Detailed workflow reporting

## 🤝 Contributing

This is currently a private development project. Contributions and feedback welcome!

## 📄 License

Developed by: bytesize, LLC. Proprietary - All rights reserved.
Development Team: Tyler Thomas

## 💬 Support

For support and questions:
- Check the **Debug Logs** tab first
- Review common troubleshooting steps above
- Ensure all configuration steps are completed

---

**Built with ❤️ for the WordPress & Elementor community**

*Transform your publishing workflow from chaotic to professional with Git-powered version control.*