document.addEventListener("DOMContentLoaded", () => {
  const buttons = document.querySelectorAll('.filter-buttons button');
  const rows = document.querySelectorAll('.tickets table tr');
  const refreshBtn = document.getElementById('refreshBtn');
  const totalCountEl = document.getElementById('totalCount');
  const openCountEl = document.getElementById('openCount');
  const inProgressCountEl = document.getElementById('inProgressCount');
  const resolvedCountEl = document.getElementById('resolvedCount');
  const rightPanel = document.getElementById('rightPanel');
  const aiSortCategoriesBtn = document.getElementById('aiSortCategories');
  const aiSortPrioritiesBtn = document.getElementById('aiSortPriorities');

  // Load saved filter
  const savedFilter = localStorage.getItem('activeFilter') || 'all';
  applyFilter(savedFilter);
  updateCounts();
  attachRowClickHandlers();

  buttons.forEach(btn => {
    btn.addEventListener('click', () => {
      const filter = btn.dataset.status;
      localStorage.setItem('activeFilter', filter);
      applyFilter(filter);
    });
  });

  // === EXISTING FUNCTION (unchanged, except for dynamic count updates) ===
  function applyFilter(filter) {
    // Update button active state
    buttons.forEach(btn => {
      btn.classList.toggle('active', btn.dataset.status === filter);
    });

    // Filter table rows (skip header)
    rows.forEach((row, index) => {
      if (index === 0) return; // skip table header
      const statusCell = row.querySelector('.status');
      const status = statusCell ? statusCell.classList[1] : ''; // e.g. "open" or "in-progress"
      row.style.display = (filter === 'all' || status === filter) ? '' : 'none';
    });

    // Update counts and reattach row click
    updateCounts();
    attachRowClickHandlers();
  }

  // === NEW FUNCTIONALITY: REFRESH & COUNT UPDATES ===
  function updateCounts() {
    let total = 0, open = 0, inProgress = 0, resolved = 0;

    rows.forEach((row, index) => {
      if (index === 0) return; // skip table header
      const statusCell = row.querySelector('.status');
      const status = statusCell ? statusCell.classList[1] : '';
      if (!status) return;

      total++;
      if (status === 'open') open++;
      if (status === 'in-progress') inProgress++;
      if (status === 'resolved') resolved++;
    });

    // Update dashboard cards if they exist
    if (totalCountEl) totalCountEl.textContent = total;
    if (openCountEl) openCountEl.textContent = open;
    if (inProgressCountEl) inProgressCountEl.textContent = inProgress;
    if (resolvedCountEl) resolvedCountEl.textContent = resolved;

    // Update small counts in filter buttons
    buttons.forEach(btn => {
      const span = btn.querySelector('.count');
      if (!span) return;
      const status = btn.dataset.status;
      if (status === 'all') span.textContent = total;
      else if (status === 'open') span.textContent = open;
      else if (status === 'in-progress') span.textContent = inProgress;
      else if (status === 'resolved') span.textContent = resolved;
    });
  }

  // === NEW: show ticket details in right panel ===
  function attachRowClickHandlers() {
    const tableRows = document.querySelectorAll('.tickets table tbody tr');
    tableRows.forEach(row => {
      row.style.cursor = 'pointer';
      row.addEventListener('click', () => showTicketDetails(row));
    });
  }

  function showTicketDetails(row) {
    const cells = row.querySelectorAll('td');
    if (cells.length < 6 || !rightPanel) return;

    const customer = cells[0].textContent.trim();
    const subject = cells[1].textContent.trim();
    const status = cells[2].textContent.trim();
    const priority = cells[3].textContent.trim();
    const created = cells[4].textContent.trim();
    const ticketId = cells[5].textContent.trim();

    rightPanel.innerHTML = `
      <h2>Ticket Details</h2>
      <p><strong>Customer:</strong> ${customer}</p>
      <p><strong>Subject:</strong> ${subject}</p>
      <p><strong>Status:</strong> ${status}</p>
      <p><strong>Priority:</strong> ${priority}</p>
      <p><strong>Created:</strong> ${created}</p>
      <p><strong>Ticket ID:</strong> ${ticketId}</p>
      <p style="margin-top: 15px;">
        <a href="ticket_view.html" class="view-link" style="color: white; text-decoration: none;">Open Full Ticket</a>
      </p>
    `;
  }

  // Reset right panel on refresh
  function resetRightPanel() {
    if (rightPanel) {
      rightPanel.innerHTML = `
        <h2>No Ticket Selected</h2>
        <p>Click a ticket from the table to view details here.</p>
      `;
    }
  }

  // Refresh button event
  if (refreshBtn) {
    refreshBtn.addEventListener('click', () => {
      refreshBtn.disabled = true;
      refreshBtn.textContent = 'Refreshing...';
      setTimeout(() => {
        updateCounts();
        resetRightPanel();
        attachRowClickHandlers();
        refreshBtn.textContent = 'Refresh';
        refreshBtn.disabled = false;
      }, 500);
    });
  }

  // === AI Sort Categories ===
  if (aiSortCategoriesBtn) {
    aiSortCategoriesBtn.addEventListener('click', async () => {
      const tableRows = document.querySelectorAll('.tickets table tbody tr');
      const tickets = [];
      tableRows.forEach(row => {
        const ticketId = row.querySelector('td:nth-child(6)')?.textContent.trim(); // Ticket ID is in 6th column
        const subject = row.querySelector('.ticket-subject')?.textContent.trim() || '';
        const message = row.querySelector('.ticket-message')?.textContent.trim() || '';
        if (ticketId) tickets.push({ ticketId, text: `${subject} ${message}`.trim() }); // Combine subject + message for classification
      });

      if (tickets.length === 0) {
        alert('No tickets to classify.');
        return;
      }

      try {
        const response = await fetch('backend/ai_classify_ticket.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ tickets }) // Send array of { ticketId, text }
        });

        const data = await response.json();
        if (data.success && data.updates) {
          // Updates were saved in PHP; just refresh the table to reflect DB changes
          loadAllTickets();
          alert('Categories updated successfully!');
        } else {
          alert('Error: ' + (data.message || 'Unknown error'));
        }
      } catch (error) {
        console.error('AI Sort Categories error:', error);
        alert('Failed to sort categories. Check console for details.');
      }
    });
  }

  // === AI Sort Priorities ===
  if (aiSortPrioritiesBtn) {
    aiSortPrioritiesBtn.addEventListener('click', async () => {
      const tableRows = document.querySelectorAll('.tickets table tbody tr');
      const tickets = [];
      tableRows.forEach(row => {
        const ticketId = row.querySelector('td:nth-child(6)')?.textContent.trim(); // Ticket ID is in 6th column
        const subject = row.querySelector('.ticket-subject')?.textContent.trim() || '';
        const message = row.querySelector('.ticket-message')?.textContent.trim() || '';
        if (ticketId) tickets.push({ ticketId, text: `${subject} ${message}`.trim() }); // Combine subject + message
      });

      if (tickets.length === 0) {
        alert('No tickets to classify.');
        return;
      }

      try {
        const response = await fetch('backend/ai_classify_priority.php', { // Fixed URL
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ tickets }) // Send array of { ticketId, text }
        });

        const data = await response.json();
        if (data.success && data.updates) {
          // Updates were saved in PHP; just refresh the table
          loadAllTickets();
          alert('Priorities updated successfully!');
        } else {
          alert('Error: ' + (data.message || 'Unknown error'));
        }
      } catch (error) {
        console.error('AI Sort Priorities error:', error);
        alert('Failed to sort priorities. Check console for details.');
      }
    });
  }
});