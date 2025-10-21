(function () {
    const walletBalanceElement = document.querySelector('[data-wallet-balance]');

    if (walletBalanceElement && window.ohTheme) {
        fetch(`${window.ohTheme.restUrl}oh-digital/v1/wallet/balance`, {
            headers: {
                'X-WP-Nonce': window.ohTheme.nonce,
            },
            credentials: 'same-origin',
        })
            .then((response) => response.json())
            .then((data) => {
                walletBalanceElement.textContent = Number.parseFloat(data.balance).toFixed(2);
            })
            .catch(() => {
                walletBalanceElement.textContent = '0.00';
            });
    }
})();
