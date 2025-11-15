// Registration Reward Plugin - Settings Manager
$(document).ready(function() {
    'use strict';

    const isActive = (pluginActive.toString().toLowerCase() === 'true' || pluginActive.toString() === '1');
    
    // Initialize
    initializeHandlers();
    updateAllChanceSums();

    /**
     * Initialize all event handlers
     */
    function initializeHandlers() {
        // Add item button
        $(document).on('click', '.add-item-btn', function() {
            const $table = $(this).closest('.tab-pane').find('.items-table tbody');
            addItemRow($table);
        });

        // Remove item button
        $(document).on('click', '.remove-item-btn', function() {
            $(this).closest('tr').fadeOut(300, function() {
                $(this).remove();
                updateChanceSum($(this).closest('.tab-pane'));
            });
        });

        // Update chance on input change
        $(document).on('input', '.chance-input, .min-count-input, .max-count-input', function() {
            const $tabPane = $(this).closest('.tab-pane');
            updateChanceSum($tabPane);
        });

        // Update item info when ID changes
        $(document).on('change', '.item-id-input', function() {
            const $row = $(this).closest('tr');
            updateItemInfo($row, $(this).val());
        });
        
        // Load item info for existing items on page load
        loadExistingItemsInfo();

        // Save settings button
        $(document).on('click', '.save-settings-btn', function(e) {
            e.preventDefault();
            const serverId = $(this).data('server-id');
            const $tabPane = $(`#server-content-${serverId}`);
            saveSettings(serverId, $tabPane);
        });
    }

    /**
     * Add new item row to table
     */
    function addItemRow($table) {
        const rowCount = $table.find('tr').length;
        const newRow = `
            <tr class="item-row">
                <td class="text-center item-index">${rowCount + 1}</td>
                <td>
                    <input type="number" 
                           class="form-control form-control-sm item-id-input" 
                           placeholder="ID" 
                           value="0">
                </td>
                <td>
                    <input type="number" 
                           class="form-control form-control-sm min-count-input" 
                           placeholder="1" 
                           min="1" 
                           value="1">
                </td>
                <td>
                    <input type="number" 
                           class="form-control form-control-sm max-count-input" 
                           placeholder="1" 
                           min="1" 
                           value="1">
                </td>
                <td>
                    <input type="number" 
                           class="form-control form-control-sm enchant-input" 
                           placeholder="0" 
                           value="0">
                </td>
                <td>
                    <input type="number" 
                           class="form-control form-control-sm chance-input" 
                           placeholder="0.00" 
                           step="0.01" 
                           min="0" 
                           max="100"
                           value="0.00">
                </td>
                <td>
                    <div class="item-info text-muted small">
                        <small>Item ID: 0</small>
                    </div>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-item-btn">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $table.append(newRow);
        updateChanceSum($table.closest('.tab-pane'));
    }

    /**
     * Update item information based on item ID
     */
    function updateItemInfo($row, itemId) {
        const $infoCell = $row.find('.item-info');
        
        if (!itemId || itemId === '0') {
            $infoCell.html('<small>Item ID: 0</small>');
            return;
        }

        // Fetch item info from the correct endpoint
        $.ajax({
            url: '/admin/client/item/info',
            type: 'POST',
            data: { itemID: itemId },
            dataType: 'json',
            success: function(response) {
                if (response && response.ok && response.item) {
                    const item = response.item;
                    const iconUrl = item.icon || '';
                    const iconHtml = iconUrl ? `<img src="${iconUrl}" alt="${item.itemName}" style="height: 24px; width: 24px; margin-right: 5px; vertical-align: middle;">` : '';
                    $infoCell.html(`
                        <small style="display: flex; align-items: center;">
                            ${iconHtml}
                            <div>
                                <strong>${item.itemName || 'Item #' + itemId}</strong><br>
                                <span class="text-muted">ID: ${itemId}</span>
                            </div>
                        </small>
                    `);
                } else {
                    $infoCell.html(`<small class="text-warning">Item #${itemId} - Not found</small>`);
                }
            },
            error: function() {
                $infoCell.html(`<small class="text-warning">Item #${itemId}</small>`);
            }
        });
    }

    /**
     * Load item information for all existing items on page load
     */
    function loadExistingItemsInfo() {
        $('.items-table tbody tr').each(function() {
            const $row = $(this);
            const itemId = parseInt($row.find('.item-id-input').val()) || 0;
            if (itemId > 0) {
                updateItemInfo($row, itemId);
            }
        });
    }

    /**
     * Validate and adjust min/max count before save
     */
    function validateMinMaxBeforeSave($minInput, $maxInput) {
        const minVal = parseInt($minInput.val()) || 1;
        const maxVal = parseInt($maxInput.val()) || 1;

        if (minVal > maxVal) {
            return false; // Invalid - min > max
        }
        
        if (maxVal < minVal) {
            return false; // Invalid - max < min
        }

        return true; // Valid
    }

    /**
     * Update chance sum for a specific tab pane
     */
    function updateChanceSum($tabPane) {
        const chances = $tabPane.find('.chance-input');
        let totalChance = 0;

        chances.each(function() {
            const val = parseFloat($(this).val()) || 0;
            totalChance += val;
        });

        // Round to 2 decimal places
        totalChance = Math.round(totalChance * 100) / 100;

        // Update sum display
        const $sumDisplay = $tabPane.find('.chance-sum');
        $sumDisplay.text(totalChance.toFixed(2));

        // Update progress bar
        const $progressBar = $tabPane.find('.chance-progress-bar');
        let progressWidth = totalChance;
        if (progressWidth > 100) progressWidth = 100;
        
        $progressBar.css('width', progressWidth + '%');

        // Change color based on sum
        if (totalChance > 100) {
            $progressBar.removeClass('bg-warning bg-success').addClass('bg-danger');
            $sumDisplay.removeClass('text-warning text-success').addClass('text-danger fw-bold');
        } else if (totalChance < 100) {
            $progressBar.removeClass('bg-danger bg-success').addClass('bg-warning');
            $sumDisplay.removeClass('text-danger text-success').addClass('text-warning fw-bold');
        } else {
            $progressBar.removeClass('bg-danger bg-warning').addClass('bg-success');
            $sumDisplay.removeClass('text-danger text-warning').addClass('text-success fw-bold');
        }
    }

    /**
     * Update all chance sums for all tabs
     */
    function updateAllChanceSums() {
        $('.tab-pane').each(function() {
            updateChanceSum($(this));
        });
    }

    /**
     * Save settings for a specific server
     */
    function saveSettings(serverId, $tabPane) {
        // Validate items
        const items = [];
        const $rows = $tabPane.find('.items-table tbody tr');

        if ($rows.length === 0) {
            alert(phrase['no_items_error'] || 'Please add at least one item');
            return;
        }

        let hasValidItems = false;

        $rows.each(function() {
            const itemId = parseInt($(this).find('.item-id-input').val()) || 0;
            if (itemId === 0) return; // Skip empty rows

            const $minInput = $(this).find('.min-count-input');
            const $maxInput = $(this).find('.max-count-input');
            const minCount = parseInt($minInput.val()) || 1;
            const maxCount = parseInt($maxInput.val()) || 1;
            const enchant = parseInt($(this).find('.enchant-input').val()) || 0;
            const chance = parseFloat($(this).find('.chance-input').val()) || 0;

            // Validate min/max before save
            if (minCount > maxCount) {
                alert(`${phrase['error_min_max'] || 'Min count cannot be greater than max count (Item ID: ' + itemId + ')'}`);
                return false;
            }

            items.push({
                itemId: itemId,
                minCount: minCount,
                maxCount: maxCount,
                enchant: enchant,
                chance: chance
            });

            hasValidItems = true;
        });

        if (!hasValidItems) {
            alert(phrase['no_valid_items'] || 'Please add at least one valid item');
            return;
        }

        // Get other settings
        const itemsCount = parseInt($tabPane.find('.items-count-input').val()) || 1;
        const allowDuplicates = $tabPane.find('.allow-duplicates-input').is(':checked');
        const allowClearRewards = $tabPane.find('.allow-clear-rewards-input').is(':checked');

        // Validate items count
        if (itemsCount < 1 || itemsCount > 100) {
            alert(phrase['error_items_count'] || 'Items count must be between 1 and 100');
            return;
        }

        // Send to server
        const data = {
            serverId: serverId,
            items: items,
            itemsCount: itemsCount,
            allowDuplicates: allowDuplicates,
            allowClearRewards: allowClearRewards
        };

        // Send as JSON with proper headers
        $.ajax({
            url: '/admin/plugin/registration_reward/setting/save',
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                responseAnalysis(response);
            },
            error: function(xhr, status, error) {
                console.error('Error saving settings:', error);
                alert('Error saving settings: ' + error);
            }
        });
    }

    // Prevent default form submission if needed
    $(document).on('submit', 'form', function(e) {
        if ($(this).closest('[data-server-id]').length === 0) {
            return true;
        }
        e.preventDefault();
    });
});

// Helper function for translations (if not already defined)
if (typeof phrase === 'undefined') {
    window.phrase = {
        'no_items_error': 'Please add at least one item',
        'error_min_max': 'Min count cannot be greater than max count',
        'no_valid_items': 'Please add at least one valid item',
        'error_items_count': 'Items count must be between 1 and 100'
    };
}

/**
 * Manual Test Section - Try Win
 */
$(document).on('click', '.manual-try-win-btn', function() {
    const serverId = $('.manual-test-server-select').val();
    
    if (!serverId) {
        showManualTestResult('error', 'Please select a server');
        return;
    }

    $.ajax({
        url: '/plugin/registration_reward/try-win',
        type: 'POST',
        data: { serverId: serverId },
        dataType: 'json',
        success: function(response) {
            if (response.ok) {
                let itemsHtml = '<div class="alert alert-success"><strong>Winned items:</strong><ul>';
                response.items.forEach(function(item) {
                    const iconUrl = item.icon || '';
                    const iconHtml = iconUrl ? `<img src="${iconUrl}" alt="${item.name}" style="height: 20px; width: 20px; margin-right: 5px; vertical-align: middle;">` : '';
                    itemsHtml += `<li style="display: flex; align-items: center;">${iconHtml}<span>${item.name} (x${item.count})</span></li>`;
                });
                itemsHtml += '</ul></div>';
                showManualTestResult('success', itemsHtml);
            } else {
                showManualTestResult('error', response.message || 'Try win failed');
            }
        },
        error: function() {
            showManualTestResult('error', 'Request failed');
        }
    });
});

/**
 * Manual Test Section - Clear Winned
 */
$(document).on('click', '.manual-clear-btn', function() {
    const serverId = $('.manual-test-server-select').val();
    
    $.ajax({
        url: '/plugin/registration_reward/clear-winned',
        type: 'POST',
        data: { serverId: serverId },
        dataType: 'json',
        success: function(response) {
            if (response.ok) {
                showManualTestResult('success', response.message || 'Rewards cleared');
            } else {
                showManualTestResult('error', response.message || 'Clear failed');
            }
        },
        error: function() {
            showManualTestResult('error', 'Request failed');
        }
    });
});

/**
 * Show test result
 */
function showManualTestResult(type, message) {
    const $result = $('#manual-test-result');
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    $result.html(`<div class="alert ${alertClass} mb-0">${message}</div>`);
}
