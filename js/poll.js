/*
	Qlassy Poll Plugin for Question2Answer
	Developed by Sourav Pan
	https://github.com/Souravpandev/qlassy-poll
	https://wpoptimizelab.com/
	
	JavaScript for Poll Functionality
	Handles poll creation, voting, and dynamic updates
*/

// Toggle poll form visibility
function togglePollForm() {
    const pollForm = document.querySelector('.qa-poll-form');
    const toggleBtn = document.querySelector('.qa-poll-toggle-btn');
    
    if (pollForm.style.display === 'none' || pollForm.style.display === '') {
        pollForm.style.display = 'block';
        toggleBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13H5v-2h14v2z"/></svg> Hide Poll';
    } else {
        pollForm.style.display = 'none';
        toggleBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg> Add Poll';
    }
}

// Add new poll option field
function addPollOption() {
    const optionsList = document.querySelector('.qa-poll-options-list');
    const optionCount = optionsList.children.length;
    
    // Check maximum options limit
    const maxOptions = 10; // This could be made configurable
    if (optionCount >= maxOptions) {
        alert('Maximum ' + maxOptions + ' options allowed.');
        return;
    }
    
    const newOption = document.createElement('div');
    newOption.className = 'qa-poll-option-input';
    newOption.innerHTML = '<input type="text" name="poll_options[]" placeholder="Option ' + (optionCount + 1) + '" class="qa-form-tall-text">';
    
    optionsList.appendChild(newOption);
}

// Handle poll voting
function votePoll(pollid, optionid, element) {
    // Prevent multiple clicks
    if (element.classList.contains('loading')) {
        return;
    }
    

    
    element.classList.add('loading');
    
    // Create form data
    const formData = new FormData();
    formData.append('pollid', pollid);
    formData.append('optionid', optionid);
    
    // Send AJAX request
    fetch(pollAjaxURL, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        // Check if the response is ok (status 200-299)
        if (!response.ok) {
            // Handle HTTP error responses (4xx, 5xx)
            return response.text().then(responseText => {
                try {
                    const errorData = JSON.parse(responseText);
                    // Don't throw an error, just return the error data
                    return { error: true, data: errorData };
                } catch (parseError) {
                    throw new Error('Server error occurred');
                }
            });
        }
        return response.json();
    })
    .then(data => {
        element.classList.remove('loading');
        
        // Check if this is an error response
        if (data.error && data.data) {
            showError(data.data.error || 'An error occurred while voting.');
            return;
        }
        
        if (data.success) {
            updatePollDisplay(data.poll_data);
            updateVoteStatus(element, data.action === 'voted');
            
            // Update all poll options to reflect current voting state
            updateAllPollOptions(pollid, data.action === 'voted' ? optionid : null);
        } else {
            showError(data.error || 'An error occurred while voting.');
        }
    })
    .catch(error => {
        element.classList.remove('loading');
        showError(error.message || 'Network error. Please try again.');
        
        // Don't update the UI on error - keep the current state
        // This prevents the vote from disappearing when there's an error
    });
}

// Update poll display with new vote counts
function updatePollDisplay(pollData) {
    const pollContainer = document.querySelector('.qa-poll-container');
    if (!pollContainer || !pollData) return;
    
    const totalVotes = pollData.total_votes;
    const hideResults = pollData.hide_results || false;
    const userVotedOption = pollData.user_voted_option || null;
    
    // Update each option
    pollData.options.forEach(option => {
        const optionElement = pollContainer.querySelector(`[data-optionid="${option.optionid}"]`);
        if (optionElement) {
            const percentage = totalVotes > 0 ? Math.round((option.votes / totalVotes) * 100) : 0;
            
            // Update vote highlighting
            if (userVotedOption && userVotedOption == option.optionid) {
                optionElement.classList.add('qa-poll-option-voted');
            } else {
                optionElement.classList.remove('qa-poll-option-voted');
            }
            
            if (!hideResults) {
                // Update progress bar fill
                const fillElement = optionElement.querySelector('.qa-poll-option-fill');
                if (fillElement) {
                    fillElement.style.width = percentage + '%';
                }
                
                // Update vote count with new format
                const votesElement = optionElement.querySelector('.qa-poll-option-votes');
                if (votesElement) {
                    votesElement.innerHTML = '<span class="vote-percentage">' + percentage + '%</span><span class="vote-count">(' + option.votes + ')</span><span class="polling_icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024"><path fill="currentColor" d="M340.864 149.312a30.592 30.592 0 0 0 0 42.752L652.736 512 340.864 831.872a30.592 30.592 0 0 0 0 42.752 29.12 29.12 0 0 0 41.728 0L714.24 534.336a32 32 0 0 0 0-44.672L382.592 149.376a29.12 29.12 0 0 0-41.728 0z"></path></svg></span>';
                }
            }
            // If results are hidden, don't update the display
        }
    });
    
    // Update total votes
    if (!hideResults) {
        const totalElement = pollContainer.querySelector('.qa-poll-total');
        if (totalElement) {
            totalElement.textContent = totalVotes + ' votes';
        }
        
        // Update chart if it exists
        updatePollChart(pollData);
    }
}

// Update poll chart with new data
function updatePollChart(pollData) {
    const pollContainer = document.querySelector('.qa-poll-container');
    if (!pollContainer || !pollData || !pollData.options) return;
    
    // Check if Chart.js is available
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js not loaded, skipping chart update');
        return;
    }
    
    // Find the chart canvas
    const chartCanvas = pollContainer.querySelector('canvas[id^="poll-chart-"]');
    if (!chartCanvas) return;
    
    // Get the chart instance
    const chartId = chartCanvas.id;
    const chartInstance = Chart.getChart(chartId);
    
    if (chartInstance) {
        // Update chart data with truncated labels for mobile
        const labels = pollData.options.map(option => {
            let optionText = option.option_text;
            if (optionText.length > 30) {
                optionText = optionText.substring(0, 27) + '...';
            }
            return optionText;
        });
        const data = pollData.options.map(option => option.votes);
        
        chartInstance.data.labels = labels;
        chartInstance.data.datasets[0].data = data;
        chartInstance.update();
    }
}

// Update vote status styling
function updateVoteStatus(element, voted) {
    if (voted) {
        element.classList.add('qa-poll-option-voted');
    } else {
        element.classList.remove('qa-poll-option-voted');
    }
}

// Update all poll options to reflect current voting state
function updateAllPollOptions(pollid, votedOptionId) {
    const pollContainer = document.querySelector('.qa-poll-container');
    if (!pollContainer) return;
    
    const allOptions = pollContainer.querySelectorAll('.qa-poll-option');
    
    allOptions.forEach(option => {
        const optionId = option.getAttribute('data-optionid');
        
        if (votedOptionId && optionId == votedOptionId) {
            // This is the option that was just voted for
            option.classList.add('qa-poll-option-voted');
            option.style.pointerEvents = 'auto'; // Allow unvoting
            option.style.opacity = '1';
        } else if (votedOptionId) {
            // This is a different option - check if changing votes is allowed
            // For now, always allow changing votes to avoid confusion
            option.classList.remove('qa-poll-option-voted');
            option.style.pointerEvents = 'auto'; // Allow voting
            option.style.opacity = '1';
        } else {
            // No vote currently - enable all options
            option.classList.remove('qa-poll-option-voted');
            option.style.pointerEvents = 'auto';
            option.style.opacity = '1';
        }
    });
}

// Show error message
function showError(message) {
    // Remove existing error messages
    const existingError = document.querySelector('.qa-poll-error');
    if (existingError) {
        existingError.remove();
    }
    
    // Create new error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'qa-poll-error';
    errorDiv.textContent = message;
    
    // Insert error message
    const pollContainer = document.querySelector('.qa-poll-container');
    if (pollContainer) {
        pollContainer.insertBefore(errorDiv, pollContainer.firstChild);
        
        // Auto-remove error after 5 seconds
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.remove();
            }
        }, 5000);
    }
}

// Show success message
function showSuccess(message) {
    // Remove existing success messages
    const existingSuccess = document.querySelector('.qa-poll-success');
    if (existingSuccess) {
        existingSuccess.remove();
    }
    
    // Create new success message
    const successDiv = document.createElement('div');
    successDiv.className = 'qa-poll-success';
    successDiv.textContent = message;
    
    // Insert success message
    const pollContainer = document.querySelector('.qa-poll-container');
    if (pollContainer) {
        pollContainer.insertBefore(successDiv, pollContainer.firstChild);
        
        // Auto-remove success after 3 seconds
        setTimeout(() => {
            if (successDiv.parentNode) {
                successDiv.remove();
            }
        }, 3000);
    }
}

// Initialize poll functionality when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers to poll options
    const pollOptions = document.querySelectorAll('.qa-poll-option');
    pollOptions.forEach(option => {
        option.addEventListener('click', function() {
            const pollid = this.getAttribute('data-pollid');
            const optionid = this.getAttribute('data-optionid');
            votePoll(pollid, optionid, this);
        });
    });
    
    // Add form validation for poll fields
    const askForm = document.querySelector('form[name="ask"]');
    if (askForm) {
        askForm.addEventListener('submit', function(e) {
            const pollQuestion = document.getElementById('poll_question');
            const pollOptions = document.querySelectorAll('input[name="poll_options[]"]');
            
            // If poll form is visible, validate poll data
            const pollForm = document.querySelector('.qa-poll-form');
            if (pollForm && pollForm.style.display !== 'none') {
                if (!pollQuestion.value.trim()) {
                    e.preventDefault();
                    alert('Please enter a poll question.');
                    pollQuestion.focus();
                    return;
                }
                
                let validOptions = 0;
                pollOptions.forEach(option => {
                    if (option.value.trim()) {
                        validOptions++;
                    }
                });
                
                if (validOptions < 2) {
                    e.preventDefault();
                    alert('Please provide at least 2 poll options.');
                    return;
                }
            }
        });
    }
});

// Add keyboard navigation for poll options
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ' ') {
        const focusedElement = document.activeElement;
        if (focusedElement && focusedElement.classList.contains('qa-poll-option')) {
            e.preventDefault();
            focusedElement.click();
        }
    }
});
