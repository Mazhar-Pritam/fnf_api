<?php
// Shared helpers for expense create / update.

function expense_categories()
{
    return ['Rent', 'Salary', 'Electricity', 'Transport', 'Internet', 'Repair', 'Other'];
}

function expense_methods()
{
    return ['Cash', 'Bank', 'Mobile Banking'];
}

function expense_read_input()
{
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new InvalidArgumentException('Invalid request data');
    }

    $expense_date = trim((string)($input['expense_date'] ?? ''));
    $category = (string)($input['category'] ?? '');
    $amount = (isset($input['amount']) && is_numeric($input['amount'])) ? round((float)$input['amount'], 2) : 0;
    $method = (string)($input['method'] ?? '');
    $note = trim((string)($input['note'] ?? ''));

    if ($expense_date === '') {
        throw new InvalidArgumentException('Expense date is required');
    }
    $date = DateTime::createFromFormat('Y-m-d', $expense_date);
    if (!$date || $date->format('Y-m-d') !== $expense_date) {
        throw new InvalidArgumentException('Expense date is not valid');
    }
    if (!in_array($category, expense_categories(), true)) {
        throw new InvalidArgumentException('Expense category is not valid');
    }
    if ($amount <= 0) {
        throw new InvalidArgumentException('Amount must be more than 0');
    }
    if (!in_array($method, expense_methods(), true)) {
        throw new InvalidArgumentException('Payment method is not valid');
    }

    return [
        'expense_date' => $expense_date,
        'category' => $category,
        'amount' => $amount,
        'method' => $method,
        'note' => $note,
    ];
}
