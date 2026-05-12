@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Budget Planner</h1>
            <p class="text-sm text-slate-600">Track income, expenses, and monthly budget goals in one place.</p>
        </div>
        <a href="{{ route('tools.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Back to Tools Hub
        </a>
    </div>

    <!-- 3rd row: right side buttons -->
    <div class="flex justify-end gap-2 mb-4">
        <button id="open-payable-modal" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-500">Add Payable</button>
        <button id="open-transaction-modal" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">Add Transaction</button>
    </div>

    <!-- 4th row: tables side by side -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Payables</h2>
                <button id="clear-payables" type="button" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100">Clear Payables</button>
            </div>
            <div class="max-h-[560px] overflow-y-auto overflow-x-auto pr-1">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-2 py-2">Item</th>
                            <th class="px-2 py-2">Amount</th>
                            <th class="px-2 py-2">Due Date</th>
                            <th class="px-2 py-2">Description</th>
                            <th class="px-2 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody id="payable-table"></tbody>
                </table>
            </div>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Transactions</h2>
                <button id="clear-all" type="button" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">Clear All</button>
            </div>
            <div class="max-h-[560px] overflow-y-auto overflow-x-auto pr-1">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-2 py-2">Type</th>
                            <th class="px-2 py-2">Category</th>
                            <th class="px-2 py-2">Note</th>
                            <th class="px-2 py-2">Amount</th>
                            <th class="px-2 py-2">Date</th>
                            <th class="px-2 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody id="entry-table"></tbody>
                </table>
            </div>
        </section>
    </div>
            <!-- Removed duplicate transactions table below -->
    <script>
    // Modal open/close logic
    document.getElementById('open-payable-modal').onclick = function() {
        document.getElementById('payable-modal').classList.remove('hidden');
    };
    document.getElementById('open-transaction-modal').onclick = function() {
        document.getElementById('transaction-modal').classList.remove('hidden');
    };
    function closePayableModal() {
        document.getElementById('payable-modal').classList.add('hidden');
        document.getElementById('payableForm').reset();
    }
    function closeTransactionModal() {
        document.getElementById('transaction-modal').classList.add('hidden');
        document.getElementById('transactionForm').reset();
    }
    // Modal form submit logic
    function addPayableModal(event) {
        event.preventDefault();
        const item = document.getElementById('modalPayableItem').value;
        const amount = parseFloat(document.getElementById('modalPayableAmount').value);
        const dueDate = document.getElementById('modalPayableDueDate').value;
        const description = document.getElementById('modalPayableDescription').value;
        if (!item || !amount) return;
        const state = JSON.parse(localStorage.getItem('tools-budget-v1') || '{}');
        state.payables = state.payables || [];
        state.payables.unshift({
            id: String(Date.now()) + '-' + Math.random().toString(36).slice(2),
            item, amount, dueDate, description
        });
        localStorage.setItem('tools-budget-v1', JSON.stringify(state));
        closePayableModal();
            render();
    }
    function addTransactionModal(event) {
        event.preventDefault();
        const type = document.getElementById('modalEntryType').value;
        const category = document.getElementById('modalEntryCategory').value;
        const note = document.getElementById('modalEntryNote').value;
        const amount = parseFloat(document.getElementById('modalEntryAmount').value);
        if (!category || !amount) return;
        const state = JSON.parse(localStorage.getItem('tools-budget-v1') || '{}');
        state.entries = state.entries || [];
        state.entries.unshift({
            id: String(Date.now()) + '-' + Math.random().toString(36).slice(2),
            type, category, note, amount,
            dateLabel: new Date().toLocaleDateString('en-PH', {year: 'numeric', month: 'short', day: '2-digit'})
        });
        localStorage.setItem('tools-budget-v1', JSON.stringify(state));
        closeTransactionModal();
            render();
    }
    </script>

    <!-- Payable Modal -->
    <div id="payable-modal" class="fixed z-30 inset-0 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-lg w-full p-6 z-40">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Add Payable</h3>
                    <button onclick="closePayableModal()" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form id="payableForm" onsubmit="addPayableModal(event)">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Item</label>
                        <input type="text" id="modalPayableItem" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Amount</label>
                        <input type="number" id="modalPayableAmount" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Due Date</label>
                        <input type="date" id="modalPayableDueDate" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                        <textarea id="modalPayableDescription" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="button" onclick="closePayableModal()" class="mr-2 px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Transaction Modal -->
    <div id="transaction-modal" class="fixed z-30 inset-0 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-lg w-full p-6 z-40">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Add Transaction</h3>
                    <button onclick="closeTransactionModal()" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form id="transactionForm" onsubmit="addTransactionModal(event)">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Type</label>
                        <select id="modalEntryType" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Category</label>
                        <input type="text" id="modalEntryCategory" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Note</label>
                        <input type="text" id="modalEntryNote" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Amount</label>
                        <input type="number" id="modalEntryAmount" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    <div class="flex justify-end">
                        <button type="button" onclick="closeTransactionModal()" class="mr-2 px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Transactions</h2>
                <button id="clear-all" type="button" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">Clear All</button>
            </div>

            <div class="max-h-[560px] overflow-y-auto overflow-x-auto pr-1">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-2 py-2">Type</th>
                            <th class="px-2 py-2">Category</th>
                            <th class="px-2 py-2">Note</th>
                            <th class="px-2 py-2">Amount</th>
                            <th class="px-2 py-2">Date</th>
                            <th class="px-2 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody id="entry-table"></tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<script>
(function () {
    const STORAGE_KEY = 'tools-budget-v1';

    const monthInput = document.getElementById('budget-month');
    const budgetInput = document.getElementById('budget-limit');
    const saveBudgetBtn = document.getElementById('save-budget');

    const typeInput = document.getElementById('entry-type');
    const categoryInput = document.getElementById('entry-category');
    const noteInput = document.getElementById('entry-note');
    const amountInput = document.getElementById('entry-amount');
    const addEntryBtn = document.getElementById('add-entry');
    const clearAllBtn = document.getElementById('clear-all');
    const payableDueDateInput = document.getElementById('payable-due-date');
    const payableDescriptionInput = document.getElementById('payable-description');
    const clearPayablesBtn = document.getElementById('clear-payables');

    const totalIncomeEl = document.getElementById('summary-income');
    const totalExpenseEl = document.getElementById('summary-expense');
    const budgetLimitEl = document.getElementById('summary-budget');
    const payableEl = document.getElementById('summary-payable');
    const balanceEl = document.getElementById('summary-balance');

    const usageLabelEl = document.getElementById('budget-usage-label');
    const usageBarEl = document.getElementById('budget-usage-bar');
    const budgetStatusEl = document.getElementById('budget-status');

    const tableEl = document.getElementById('entry-table');
    const payableTableEl = document.getElementById('payable-table');
    const entryTypeInput = document.getElementById('entry-type');
    const entryCategoryGroup = document.getElementById('entry-category-group');
    const entryNoteGroup = document.getElementById('entry-note-group');
    const entryAmountGroup = document.getElementById('entry-amount-group');
    const payableDueDateGroup = document.getElementById('payable-due-date-group');
    const payableDescriptionGroup = document.getElementById('payable-description-group');

    const state = loadState();

    function currency(value) {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
        }).format(value || 0);
    }

    function loadState() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) {
                return {
                    monthLabel: '',
                    budgetLimit: 0,
                    entries: [],
                    payables: []
                };
            }

            const parsed = JSON.parse(raw);
            return {
                monthLabel: parsed.monthLabel || '',
                budgetLimit: Number(parsed.budgetLimit || 0),
                entries: Array.isArray(parsed.entries) ? parsed.entries : [],
                payables: Array.isArray(parsed.payables) ? parsed.payables : []
            };
        } catch (error) {
            return {
                monthLabel: '',
                budgetLimit: 0,
                entries: [],
                payables: []
            };
        }
    }

    function saveState() {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    }

    function totals() {
        let income = 0;
        let expense = 0;
        let payable = 0;

        state.entries.forEach(function (entry) {
            if (entry.type === 'income') {
                income += entry.amount;
            } else {
                expense += entry.amount;
            }
        });

        state.payables.forEach(function (item) {
            payable += item.amount;
        });

        return {
            income: income,
            expense: expense,
            payable: payable,
            balance: income - expense - payable
        };
    }

    function renderSummary() {
        const result = totals();
        totalIncomeEl.textContent = currency(result.income);
        totalExpenseEl.textContent = currency(result.expense);
        budgetLimitEl.textContent = currency(state.budgetLimit);
        payableEl.textContent = currency(result.payable);
        balanceEl.textContent = currency(result.balance);

        const usage = state.budgetLimit > 0 ? (result.expense / state.budgetLimit) * 100 : 0;
        const boundedUsage = Math.max(0, Math.min(usage, 100));

        usageLabelEl.textContent = usage.toFixed(1) + '%';
        usageBarEl.style.width = boundedUsage + '%';

        usageBarEl.className = 'h-full rounded-full transition-all ';
        if (usage >= 100) {
            usageBarEl.classList.add('bg-rose-500');
            budgetStatusEl.textContent = 'Budget exceeded. Review your expenses.';
            budgetStatusEl.className = 'mt-3 text-sm font-medium text-rose-700';
            return;
        }

        if (usage >= 85) {
            usageBarEl.classList.add('bg-amber-500');
            budgetStatusEl.textContent = 'Warning: You are close to your budget limit.';
            budgetStatusEl.className = 'mt-3 text-sm font-medium text-amber-700';
            return;
        }

        usageBarEl.classList.add('bg-emerald-500');
        budgetStatusEl.textContent = state.budgetLimit > 0
            ? 'Healthy budget. Keep monitoring your spending.'
            : 'Set your budget and start tracking entries.';
        budgetStatusEl.className = 'mt-3 text-sm font-medium text-slate-700';
    }

    function renderEntries() {
        if (!state.entries.length) {
            tableEl.innerHTML = '<tr><td colspan="6" class="px-2 py-6 text-center text-sm text-slate-500">No transactions yet.</td></tr>';
            return;
        }

        tableEl.innerHTML = state.entries.map(function (entry) {
            const typeBadge = entry.type === 'income'
                ? '<span class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Income</span>'
                : '<span class="rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700">Expense</span>';

            return '<tr class="border-b border-slate-100">'
                + '<td class="px-2 py-2">' + typeBadge + '</td>'
                + '<td class="px-2 py-2 text-slate-700">' + escapeHtml(entry.category) + '</td>'
                + '<td class="px-2 py-2 text-slate-600">' + escapeHtml(entry.note || '-') + '</td>'
                + '<td class="px-2 py-2 font-semibold ' + (entry.type === 'income' ? 'text-emerald-700' : 'text-rose-700') + '">' + currency(entry.amount) + '</td>'
                + '<td class="px-2 py-2 text-slate-500">' + escapeHtml(entry.dateLabel) + '</td>'
                + '<td class="px-2 py-2"><button type="button" data-id="' + entry.id + '" class="delete-entry rounded-md border border-slate-200 bg-white px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">Delete</button></td>'
                + '</tr>';
        }).join('');

        tableEl.querySelectorAll('.delete-entry').forEach(function (button) {
            button.addEventListener('click', function () {
                const id = button.getAttribute('data-id');
                state.entries = state.entries.filter(function (entry) {
                    return entry.id !== id;
                });
                saveState();
                render();
            });
        });
    }

    function formatDueDate(value) {
        if (!value) {
            return '-';
        }

        const date = new Date(value + 'T00:00:00');
        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return date.toLocaleDateString('en-PH', {
            year: 'numeric',
            month: 'short',
            day: '2-digit'
        });
    }

    function resetPayableForm() {
        editingPayableId = null;
        payableItemInput.value = '';
        payableAmountInput.value = '';
        payableDueDateInput.value = '';
        payableDescriptionInput.value = '';
        addPayableBtn.textContent = 'Add Payable';
        cancelEditPayableBtn.classList.add('hidden');
    }

    function renderPayables() {
        if (!state.payables.length) {
            payableTableEl.innerHTML = '<tr><td colspan="5" class="px-2 py-6 text-center text-sm text-slate-500">No payables yet.</td></tr>';
            return;
        }

        payableTableEl.innerHTML = state.payables.map(function (item) {
            return '<tr class="border-b border-slate-100">'
                + '<td class="px-2 py-2 text-slate-700">' + escapeHtml(item.item) + '</td>'
                + '<td class="px-2 py-2 font-semibold text-amber-700">' + currency(item.amount) + '</td>'
                + '<td class="px-2 py-2 text-slate-600">' + escapeHtml(formatDueDate(item.dueDate)) + '</td>'
                + '<td class="px-2 py-2 text-slate-600">' + escapeHtml(item.description || '-') + '</td>'
                + '<td class="px-2 py-2">'
                + '<button type="button" data-id="' + item.id + '" class="edit-payable mr-2 rounded-md border border-blue-200 bg-blue-50 px-2 py-1 text-xs text-blue-700 hover:bg-blue-100">Edit</button>'
                + '<button type="button" data-id="' + item.id + '" class="delete-payable rounded-md border border-slate-200 bg-white px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">Delete</button>'
                + '</td>'
                + '</tr>';
        }).join('');

        payableTableEl.querySelectorAll('.delete-payable').forEach(function (button) {
            button.addEventListener('click', function () {
                const id = button.getAttribute('data-id');
                state.payables = state.payables.filter(function (item) {
                    return item.id !== id;
                });

                if (editingPayableId === id) {
                    resetPayableForm();
                }

                saveState();
                render();
            });
        });

        payableTableEl.querySelectorAll('.edit-payable').forEach(function (button) {
            button.addEventListener('click', function () {
                const id = button.getAttribute('data-id');
                const found = state.payables.find(function (item) {
                    return item.id === id;
                });

                if (!found) {
                    return;
                }

                editingPayableId = found.id;
                payableItemInput.value = found.item;
                payableAmountInput.value = String(found.amount);
                payableDueDateInput.value = found.dueDate || '';
                payableDescriptionInput.value = found.description || '';
                addPayableBtn.textContent = 'Update Payable';
                cancelEditPayableBtn.classList.remove('hidden');
            });
        });
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function render() {
        monthInput.value = state.monthLabel;
        budgetInput.value = state.budgetLimit ? String(state.budgetLimit) : '';
        renderSummary();
        renderEntries();
        renderPayables();
    }

    saveBudgetBtn.addEventListener('click', function () {
        state.monthLabel = monthInput.value.trim();
        state.budgetLimit = Math.max(0, Number(budgetInput.value || 0));
        saveState();
        renderSummary();
    });

    addEntryBtn.addEventListener('click', function () {
        const type = typeInput.value;
        if (type === 'payable') {
            const item = categoryInput.value.trim();
            const amount = Number(amountInput.value || 0);
            const dueDate = payableDueDateInput.value;
            const description = payableDescriptionInput.value.trim();
            if (!item) {
                categoryInput.focus();
                return;
            }
            if (!Number.isFinite(amount) || amount <= 0) {
                amountInput.focus();
                return;
            }
            state.payables.unshift({
                id: String(Date.now()) + '-' + Math.random().toString(36).slice(2),
                item: item,
                amount: amount,
                dueDate: dueDate,
                description: description
            });
            categoryInput.value = '';
            amountInput.value = '';
            payableDueDateInput.value = '';
            payableDescriptionInput.value = '';
            saveState();
            render();
            return;
        }
        // income/expense
        const category = categoryInput.value.trim();
        const note = noteInput.value.trim();
        const amount = Number(amountInput.value || 0);
        if (!category) {
            categoryInput.focus();
            return;
        }
        if (!Number.isFinite(amount) || amount <= 0) {
            amountInput.focus();
            return;
        }
        state.entries.unshift({
            id: String(Date.now()) + '-' + Math.random().toString(36).slice(2),
            type: type,
            category: category,
            note: note,
            amount: amount,
            dateLabel: new Date().toLocaleDateString('en-PH', {
                year: 'numeric',
                month: 'short',
                day: '2-digit'
            })
        });
        categoryInput.value = '';
        noteInput.value = '';
        amountInput.value = '';
        saveState();
        render();
    });
    // Show/hide fields for Payable
    window.toggleEntryFields = function () {
        const type = entryTypeInput.value;
        if (type === 'payable') {
            entryCategoryGroup.querySelector('label').textContent = 'Item';
            entryCategoryGroup.querySelector('input').placeholder = 'e.g. Electricity Bill';
            entryNoteGroup.style.display = 'none';
            payableDueDateGroup.style.display = '';
            payableDescriptionGroup.style.display = '';
        } else {
            entryCategoryGroup.querySelector('label').textContent = 'Category';
            entryCategoryGroup.querySelector('input').placeholder = 'e.g. Salary, Food, Utilities';
            entryNoteGroup.style.display = '';
            payableDueDateGroup.style.display = 'none';
            payableDescriptionGroup.style.display = 'none';
        }
    };
    toggleEntryFields();

    clearAllBtn.addEventListener('click', function () {
        state.entries = [];
        saveState();
        render();
    });

    addPayableBtn.addEventListener('click', function () {
        const item = payableItemInput.value.trim();
        const amount = Number(payableAmountInput.value || 0);
        const dueDate = payableDueDateInput.value;
        const description = payableDescriptionInput.value.trim();

        if (!item) {
            payableItemInput.focus();
            return;
        }

        if (!Number.isFinite(amount) || amount <= 0) {
            payableAmountInput.focus();
            return;
        }

        if (editingPayableId) {
            state.payables = state.payables.map(function (entry) {
                if (entry.id !== editingPayableId) {
                    return entry;
                }

                return {
                    id: entry.id,
                    item: item,
                    amount: amount,
                    dueDate: dueDate,
                    description: description
                };
            });
        } else {
            state.payables.unshift({
                id: String(Date.now()) + '-' + Math.random().toString(36).slice(2),
                item: item,
                amount: amount,
                dueDate: dueDate,
                description: description
            });
        }

        resetPayableForm();
        saveState();
        render();
    });

    cancelEditPayableBtn.addEventListener('click', function () {
        resetPayableForm();
    });

    clearPayablesBtn.addEventListener('click', function () {
        state.payables = [];
        resetPayableForm();
        saveState();
        render();
    });

    resetPayableForm();
    render();
})();
</script>
@endsection
