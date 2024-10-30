document.addEventListener('DOMContentLoaded', () => {
    const transactionsList = document.getElementById('transactions-list');
    const transactionModal = document.getElementById('transaction-modal');
    const closeModal = document.querySelector('.close');
    const addTransactionBtn = document.getElementById('add-transaction');
    const transactionForm = document.getElementById('transaction-form');

    function fetchTransactions() {
        fetch('tran_process.php?action=fetch')
            .then(response => response.json())
            .then(data => renderTransactions(data));
    }

    function renderTransactions(transactions) {
        transactionsList.innerHTML = '';

        let totalIncome = 0;
        let totalExpense = 0;

        transactions.forEach(tran => {
            const tranDiv = document.createElement('div');
            tranDiv.classList.add('transaction');
            tranDiv.innerHTML = `
                <p>${tran.TranTitle} - ${tran.TranType} - RM${tran.TranAmount.toFixed(2)} - ${tran.TranDate}</p>
                <button onclick="deleteTransaction(${tran.TranID})">Delete</button>
            `;
            transactionsList.appendChild(tranDiv);

            if (tran.TranType === 'Income') totalIncome += tran.TranAmount;
            else totalExpense += tran.TranAmount;
        });

        document.getElementById('income').innerText = `RM${totalIncome.toFixed(2)}`;
        document.getElementById('expense').innerText = `RM${totalExpense.toFixed(2)}`;
        document.getElementById('total-balance').innerText = `RM${(totalIncome - totalExpense).toFixed(2)}`;
    }

    addTransactionBtn.addEventListener('click', () => {
        transactionModal.style.display = 'block';
    });

    closeModal.addEventListener('click', () => {
        transactionModal.style.display = 'none';
    });

    transactionForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(transactionForm);

        fetch('tran_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.success || data.error);
            transactionModal.style.display = 'none';
            transactionForm.reset();
            fetchTransactions();
        });
    });

    window.deleteTransaction = (tranID) => {
        if (confirm('Are you sure you want to delete this transaction?')) {
            fetch(`tran_process.php?action=delete&TranID=${tranID}`)
                .then(response => response.json())
                .then(data => {
                    alert(data.success || data.error);
                    fetchTransactions();
                });
        }
    };

    fetchTransactions();
});
