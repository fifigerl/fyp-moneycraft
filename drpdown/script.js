document.addEventListener('DOMContentLoaded', () => {
    const transactionsList = document.getElementById('transactions-list');
    const transactionModal = document.getElementById('transaction-modal');
    const closeModal = document.querySelector('.close');
    const addTransactionBtn = document.getElementById('add-transaction');
    const transactionForm = document.getElementById('transaction-form');

    // Fetch transactions when the page loads
    async function fetchTransactions() {
        try {
            const response = await fetch('tran_process.php?action=fetch');
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
        transactionModal.style.display = 'block';
    });

    // Close the transaction modal
    closeModal.addEventListener('click', () => {
        transactionModal.style.display = 'none';
    });

    // Handle form submission to add a new transaction
    transactionForm.addEventListener('submit', async (e) => {
        e.preventDefault(); // Prevent form reload immediately

        // Disable submit button to prevent multiple submissions
        const submitButton = transactionForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;

        const formData = new FormData(transactionForm);

        try {
            const response = await fetch('tran_process.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            alert(data.success || data.error);
            transactionModal.style.display = 'none';
            transactionForm.reset();
            fetchTransactions(); // Refresh the transaction list
        } catch (error) {
            console.error("Error submitting transaction:", error);
        } finally {
            // Re-enable the submit button after the request completes
            submitButton.disabled = false;
        }
    });

    // Function to delete a transaction
    window.deleteTransaction = async (tranID) => {
        if (confirm('Are you sure you want to delete this transaction?')) {
            try {
                const response = await fetch(`tran_process.php?action=delete&TranID=${tranID}`);
                const data = await response.json();

                alert(data.success || data.error);
                fetchTransactions(); // Refresh the transaction list
            } catch (error) {
                console.error("Error deleting transaction:", error);
            }
        }
    };

    // Initial fetch of transactions
    fetchTransactions();
});
