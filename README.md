# Qlassy Poll Plugin for Question2Answer

A comprehensive polling plugin for Question2Answer that allows users to create polls with multiple options, vote on them, and view real-time results. Features include AJAX-powered voting, customizable poll settings, admin controls, badge system integration, responsive design, and performance optimizations.

**Developed by:** [Sourav Pan](https://github.com/Souravpandev)  
**Website:** [WP Optimize Lab](https://wpoptimizelab.com/)  
**GitHub:** [Souravpandev](https://github.com/Souravpandev)  
**License:** GPL v3  
**Version:** 1.0.0  
**Compatibility:** Question2Answer 1.8+

## 🚀 **Core Features**

### **📊 Poll Creation & Management**
- **Easy Poll Creation**: Add polls directly to questions with an intuitive form interface
- **Multiple Options Support**: Create polls with 2-10 options (configurable via admin settings)
- **Dynamic Option Addition**: Add/remove poll options on the fly during creation
- **Form Validation**: Real-time validation for poll questions and options
- **Optional Closing Date**: Set automatic poll closing dates

### **🗳️ Voting System**
- **Real-time Voting**: AJAX-powered voting with instant results updates
- **Single Vote Mode**: Users can only vote for one option per poll
- **Vote Changing**: Users can change their votes (configurable via admin settings)
- **Login Required**: Only logged-in users can vote in polls
- **Error Handling**: Proper error messages for duplicate voting attempts

### **📈 Visual Results & Analytics**
- **Progress Bars**: Visual progress bars showing vote percentages
- **Bar Chart Visualization**: Interactive Chart.js bar charts for poll results (optional)
- **Real-time Updates**: Charts and progress bars update instantly when votes are cast
- **Responsive Charts**: Charts adapt perfectly to desktop and mobile devices
- **Vote Counters**: Display total votes and individual option counts
- **Percentage Display**: Show vote percentages for each option

### **👥 User Experience Features**
- **Voter Avatars**: Display avatars of users who voted (up to 8 in stack)
- **Voter Count**: Show additional voter count for polls with many participants
- **Interactive Elements**: Hover effects and smooth transitions
- **Keyboard Navigation**: Full keyboard accessibility support
- **Loading States**: Visual feedback during voting operations
- **Error Handling**: Graceful error messages and fallbacks

### **🎨 Design & Styling**
- **Modern UI**: Clean, professional design that matches Q2A themes
- **Responsive Design**: Perfect display on desktop, tablet, and mobile devices
- **Theme Compatibility**: Works seamlessly with all major Q2A themes
- **Professional Icons**: SVG icons for enhanced visual appeal

## ⚙️ **Admin Configuration**

### **🔧 General Settings**
- **Enable/Disable Polls**: Master switch to turn polling functionality on/off
- **Show Chart Visualization**: Enable/disable bar chart display for poll results

### **📊 Poll Behavior Settings**
- **Show Results After Close**: Hide results until poll closing date (only works if closing date is set)
- **Allow Changing Votes**: Enable/disable vote modification after initial vote

### **📏 Poll Limits**
- **Maximum Poll Options**: Configure maximum options per poll (default: 10)
- **Minimum Poll Options**: Configure minimum options required per poll (default: 2)

### **🏆 Badge & Points System**
- **Badge Integration**: Award badges for voting and poll creation achievements
- **Points for Voting**: Award points when users vote (default: 1 point)
- **Points for Creating**: Award points when users create polls (default: 5 points)

## 🏆 **Badge System**

### **Poll Creation Badges**
- **Poll Creator**: Created your first poll
- **Poll Enthusiast**: Created 5 polls
- **Poll Master**: Created 10 polls
- **Poll Expert**: Created 25 polls
- **Poll Legend**: Created 50 polls

### **Voting Badges**
- **First Voter**: Cast your first vote
- **Active Voter**: Voted in 10 polls
- **Dedicated Voter**: Voted in 25 polls
- **Voting Champion**: Voted in 50 polls
- **Voting Legend**: Voted in 100 polls

### **Popular Poll Badges**
- **Trendsetter**: Created a popular poll
- **Influencer**: Created 3 popular polls
- **Viral Creator**: Created 5 popular polls

## 📁 **File Structure**

```
qlassy-poll/
├── metadata.json              # Plugin metadata and version info
├── qa-plugin.php              # Main plugin registration file
├── qa-poll-layer.php          # Theme layer for UI integration
├── qa-poll-event.php          # Event handler for poll creation
├── qa-poll-ajax.php           # AJAX handler for voting operations
├── qa-poll-admin.php          # Admin settings and configuration
├── qa-poll-badges.php         # Badge system integration
├── css/
│   ├── poll.css               # Original CSS (development)
│   └── poll.min.css           # Minified CSS (production)
└── js/
    ├── poll.js                # Original JavaScript (development)
    └── poll.min.js            # Minified JavaScript (production)
```

## 🛠️ **Installation Guide**

### **Step 1: Upload Files**
```bash
# Copy the qlassy-poll folder to your Q2A plugins directory
cp -r qlassy-poll /path/to/your/q2a/qa-plugin/
```

### **Step 2: Install Plugin**
1. Go to **Admin → Plugins** in your Q2A admin panel
2. Find "Qlassy Poll" in the plugin list
3. Click **"Install"** to activate the plugin
4. The plugin will automatically create required database tables

### **Step 3: Configure Settings**
1. Go to **Admin → Layout → Qlassy Poll Settings**
2. Configure your preferred poll settings:
   - Enable/disable polling functionality
   - Set chart visualization preferences
   - Configure voting policies
   - Set option limits
   - Configure badge and points system
3. Click **"Save Changes"**

## 📖 **Usage Guide**

### **Creating a Poll**

1. **Navigate to Ask Page**: Go to the "Ask a Question" page
2. **Fill Question Details**: Enter your question title and content
3. **Add Poll**: Click the "Add Poll" button to expand the poll form
4. **Enter Poll Question**: Type your poll question
5. **Add Options**: Enter poll options (minimum 2, maximum 10)
6. **Add More Options**: Click "+ Add Option" to add additional options
7. **Configure Settings**: Set optional closing date
8. **Submit**: Click "Ask Question" to create your poll

### **Voting on Polls**

1. **View Poll**: Navigate to any question containing a poll
2. **See Options**: View all available poll options with current results
3. **Cast Vote**: Click on any option to vote
4. **Change Vote**: Click again to remove your vote or select a different option (if enabled)
5. **View Results**: See real-time updates to vote counts and percentages
6. **Chart Visualization**: View the interactive bar chart (if enabled)

### **Admin Management**

1. **Access Settings**: Go to Admin → Layout → Qlassy Poll Settings
2. **Configure Behavior**: Set voting policies and display options
3. **Enable Features**: Turn on/off chart visualization and other features
4. **Monitor Usage**: Track poll creation and voting activity

## 🎨 **Customization Guide**

### **Styling Customization**

The plugin uses CSS classes that can be easily customized:

```css
/* Main poll container */
.qa-poll-container { }

/* Poll form in ask page */
.qa-poll-field { }

/* Individual poll options */
.qa-poll-option { }

/* Voted option styling */
.qa-poll-option-voted { }

/* Chart container */
.qa-poll-chart-container { }

/* Progress bars */
.qa-poll-option-fill { }

/* Error messages */
.qa-poll-error { }

/* Success messages */
.qa-poll-success { }
```

### **JavaScript Customization**

Key functions available for customization:

```javascript
// Toggle poll form visibility
function togglePollForm() { }

// Add new poll option
function addPollOption() { }

// Handle voting
function votePoll(pollid, optionid, element) { }

// Update poll display
function updatePollDisplay(pollData) { }

// Update chart visualization
function updatePollChart(pollData) { }

// Show error message
function showError(message) { }

// Show success message
function showSuccess(message) { }
```

## 🔧 **Technical Requirements**

### **Server Requirements**
- **PHP**: 5.4 or higher
- **MySQL**: 5.0 or higher
- **Question2Answer**: 1.8 or higher
- **Memory**: Minimum 64MB PHP memory limit

### **Browser Support**
- **Chrome**: 60+
- **Firefox**: 55+
- **Safari**: 12+
- **Edge**: 79+
- **Mobile Browsers**: iOS Safari 12+, Chrome Mobile 60+

### **Database Tables**
The plugin creates four optimized tables:
- `qa_polls`: Poll information and settings
- `qa_poll_options`: Poll options and vote counts
- `qa_poll_votes`: Individual user votes with tracking
- `qa_poll_user_stats`: User statistics for badges
- `qa_poll_badges`: Badge awards and achievements

## 🐛 **Troubleshooting**

### **Common Issues**

**Poll not appearing on ask page:**
- Verify plugin is installed and enabled
- Check browser console for JavaScript errors
- Ensure theme layer is loading correctly

**Voting not working:**
- Check AJAX requests are not blocked
- Verify `poll-vote` page is accessible
- Confirm user permissions and login status

**Chart not displaying:**
- Ensure "Show Chart Visualization" is enabled in admin settings
- Check Chart.js is loading from CDN
- Verify poll has votes and results are visible

**Database errors:**
- Run plugin installation again
- Check all required tables exist
- Verify database permissions

### **Performance Issues**

**Slow loading:**
- Check server resources and PHP memory
- Verify database indexes are created
- Monitor AJAX request performance

**Chart rendering issues:**
- Check Chart.js CDN availability
- Verify responsive settings
- Test on different screen sizes

## 📊 **Performance Optimizations**

### **Asset Optimization**
- **Minified CSS/JS**: Production-ready minified assets
- **Conditional Loading**: Assets only load on relevant pages
- **CDN Integration**: Chart.js loaded from reliable CDN

### **Database Optimizations**
- **Efficient Queries**: Optimized database queries with JOINs
- **Indexed Tables**: Proper database indexing for fast queries
- **Minimal Queries**: Reduced database load with smart query design

## 🤝 **Support & Development**

### **Getting Help**
- **Documentation**: Check this README for usage instructions
- **GitHub Issues**: Report bugs and feature requests
- **Developer Contact**: Reach out to the plugin author

### **Contributing**
- **Code Quality**: Follow Q2A coding standards
- **Testing**: Test on multiple themes and devices
- **Documentation**: Update docs for new features

## 📝 **Changelog**

### **Version 1.0.0 (2025-01-28)**
- ✅ **Initial Release**: Comprehensive polling functionality
- ✅ **AJAX Voting**: Real-time voting with instant updates
- ✅ **Chart Visualization**: Interactive Chart.js bar charts
- ✅ **Responsive Design**: Perfect mobile and desktop experience
- ✅ **Admin Controls**: Extensive configuration options
- ✅ **Performance Optimized**: Minified assets and efficient queries
- ✅ **Badge Integration**: Achievement system for engagement
- ✅ **Modern UI/UX**: Professional design and interactions
- ✅ **Database Optimization**: Efficient queries and indexing
- ✅ **Error Handling**: Comprehensive error management
- ✅ **Accessibility**: Keyboard navigation and screen reader support

## 📄 **License**

This plugin is licensed under the **GNU General Public License v3 (GPLv3)**.

**Copyright (C) 2025 Sourav Pan**

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

## 👨‍💻 **Developer Information**

**Developer**: Sourav Pan  
**Website**: https://wpoptimizelab.com/  
**GitHub**: https://github.com/Souravpandev  
**Repository**: https://github.com/Souravpandev/qlassy-poll  
**Support**: Create issues on GitHub or contact via website

---

**Qlassy Poll Plugin** - Professional polling solution for Question2Answer communities.
