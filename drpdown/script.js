document.addEventListener('DOMContentLoaded', () => {
    const transactionsList = document.getElementById('transactions-list');
    const transactionModal = document.getElementById('transaction-modal');
    const closeModal = document.querySelector('.close');
    const addTransactionBtn = document.getElementById('add-transaction');
    const transactionForm = document.getElementById('transaction-form');
    let submitted = false; // Flag to prevent multiple submissions

    // Fetch transactions when the page loads
    async function fetchTransactions() {
        try {
            const response = await fetch('tran_process.php', {
                method: 'GET'
            });
            const data = await response.json();
            renderTransactions(data);
        } catch (error) {
            console.error("Error fetching transactions:", error);
        }
    }

    // Render transactions on the page
    function renderTransactions(transactions) {
        transactionsList.innerHTML = ''; // Clear list to avoid duplication

        let totalIncome = 0;
        let totalExpense = 0;

        transactions.forEach(tran => {
            const tranDiv = document.createElement('div');
            tranDiv.classList.add('transaction');
            tranDiv.innerHTML = `
                <p>${tran.TranTitle} - ${tran.TranType} - RM${parseFloat(tran.TranAmount).toFixed(2)} - ${tran.TranDate}</p>
                <button onclick="deleteTransaction(${tran.TranID})">Delete</button>
                <button onclick='editTransaction(${JSON.stringify(tran)})'>Edit</button>
            `;
            transactionsList.appendChild(tranDiv);

            if (tran.TranType === 'Income') totalIncome += parseFloat(tran.TranAmount);
            else totalExpense += parseFloat(tran.TranAmount);
        });

        document.getElementById('income').innerText = `RM${totalIncome.toFixed(2)}`;
        document.getElementById('expense').innerText = `RM${totalExpense.toFixed(2)}`;
        document.getElementById('total-balance').innerText = `RM${(totalIncome - totalExpense).toFixed(2)}`;
    }

    // Show the transaction modal
    addTransactionBtn.addEventListener('click', () => {
        transactionForm.reset(); // Reset form for new transaction
        document.getElementById('tran_id').value = ''; // Clear transaction ID
        transactionModal.style.display = 'block';
    });

    // Close the transaction modal
    closeModal.addEventListener('click', () => {
        transactionModal.style.display = 'none';
    });

    // Handle form submission to add or edit a transaction
    transactionForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (submitted) return;
        submitted = true;

        const formData = new FormData(transactionForm);
        formData.append("action", "save"); // Ensure action is specified for save

        try {
            const response = await fetch('tran_process.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            alert(data.success || data.error);
            if (data.success) {
                transactionModal.style.display = 'none';
                transactionForm.reset();
                fetchTransactions();
            }
        } catch (error) {
            console.error("Error submitting transaction:", error);
        } finally {
            submitted = false;
        }
    });

    // Delete a transaction
    window.deleteTransaction = async (tranID) => {
        if (confirm('Are you sure you want to delete this transaction?')) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('tran_id', tranID);

            try {
                const response = await fetch('tran_process.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                alert(data.success || data.error);
                fetchTransactions();
            } catch (error) {
                console.error("Error deleting transaction:", error);
            }
        }
    };

    // Edit transaction
    window.editTransaction = (tran) => {
        document.getElementById('tran_id').value = tran.TranID;
        document.getElementById('tran_title').value = tran.TranTitle;
        document.getElementById('tran_type').value = tran.TranType;
        document.getElementById('tran_category').value = tran.TranCat;
        document.getElementById('tran_amount').value = tran.TranAmount;
        document.getElementById('tran_date').value = tran.TranDate;
        transactionModal.style.display = 'block';
    };

    // Fetch categories when the page loads
    async function fetchCategories() {
        try {
            const response = await fetch('category_process.php', {
                method: 'GET'
            });
            const data = await response.json();
            renderCategories(data);
        } catch (error) {
            console.error("Error fetching categories:", error);
        }
    }

    // Render categories in the dropdown and list
    function renderCategories(categories) {
        const categorySelect = document.getElementById('tran_category');
        const categoryList = document.getElementById('category-list');
        categorySelect.innerHTML = '';
        categoryList.innerHTML = '';

        categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category.name;
            option.textContent = category.name;
            categorySelect.appendChild(option);

            const categoryDiv = document.createElement('div');
            categoryDiv.textContent = category.name;
            categoryList.appendChild(categoryDiv);
        });
    }

    // Handle form submission to add a new category
    document.getElementById('category-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const newCategory = document.getElementById('new_category').value;

        try {
            const response = await fetch('category_process.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'add', name: newCategory }),
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await response.json();
            alert(data.success || data.error);
            if (data.success) {
                fetchCategories();
            }
        } catch (error) {
            console.error("Error adding category:", error);
        }
    });

    // Initial fetch of transactions and categories
    fetchTransactions();
    fetchCategories();
});
