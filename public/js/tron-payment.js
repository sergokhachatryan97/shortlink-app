/**
 * Tron Payment Gateway Widget
 * Version: 2.1.2
 * Built: 2025-07-10 16:43:26
 */

(function (window) {
    'use strict';

    if (window.TronPayment) return;

    const DEFAULT_CONFIG = {
        apiUrl: 'https://coinrush.link/store',
        storeKey: null,
        theme: {
            primaryColor: '#3B82F6',
            successColor: '#10B981',
            errorColor: '#EF4444',
            warningColor: '#F59E0B',
            backgroundColor: '#FFFFFF',
            textColor: '#111827',
            borderRadius: '8px',
            fontFamily: 'inherit'
        },
        timeout: 600000,
        pollingInterval: 5000,
        qrCodeSize: 200
    };

    class TronPaymentWidget {
        constructor() {
            this.config = {...DEFAULT_CONFIG};
            this.currentPayment = null;
            this.pollingTimer = null;
            this.countdownTimer = null;
            this.modal = null;
            this.escapeHandler = null;
            this.isInitialized = false;
            this.version = '2.1.2';
            this.buildTime = '1752155006';
        }

        init(config = {}) {
            this.config = {...this.config, ...config};

            if (!this.config.storeKey) {
                throw new Error('storeKey is required for TronPayment widget');
            }

            if (!this.config.apiUrl) {
                this.config.apiUrl = this.detectApiUrl();
            }

            this.isInitialized = true;
            this.injectStyles();
            console.log(`TronPayment widget v${this.version} initialized`);
        }

        async openPayment(options = {}) {
            if (!this.isInitialized) {
                throw new Error('TronPayment widget not initialized. Call TronPayment.init() first.');
            }

            try {
                const payment = await this.createPayment(options);

                if (!payment) {
                    throw new Error('Payment creation returned null or undefined');
                }

                this.currentPayment = {
                    ...options,
                    ...payment,
                    theme: options.theme || payment.theme || this.config.theme
                };

                this.showModal();
                this.startPaymentMonitoring();

            } catch (error) {
                console.error('TronPayment: Failed to create payment:', error);
                this.currentPayment = null;
                if (options.onError) {
                    options.onError(error);
                } else {
                    alert('Failed to create payment: ' + error.message);
                }
            }
        }

        async createPayment(options) {
            const headers = {
                'Content-Type': 'application/json'
            };

            if (this.config.storeKey) {
                headers['X-Store-Key'] = this.config.storeKey;
            }

            const payload = {
                transaction_id: options.transactionId || 'tx_' + Date.now(),
                amount_usd: options.amount.toString()
            };

            if (options.asset) {
                payload.asset = options.asset;
            }

            const response = await fetch(`${this.config.apiUrl}/payment/create`, {
                method: 'POST',
                headers: headers,
                body: JSON.stringify(payload)
            });

            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                throw new Error(`Invalid response: ${response.status} ${response.statusText}. Check your store key and domain settings.`);
            }

            if (!response.ok) {
                throw new Error(data.message || `HTTP ${response.status}: ${response.statusText}`);
            }

            if (!data.success) {
                throw new Error(data.message || 'API returned success: false');
            }

            if (!data.data) {
                throw new Error('API response missing data field');
            }

            return data.data;
        }

        async checkPaymentStatus(transactionId) {
            const headers = {};

            if (this.config.storeKey) {
                headers['X-Store-Key'] = this.config.storeKey;
            }

            const response = await fetch(`${this.config.apiUrl}/payment/${transactionId}/status`, {
                headers: headers
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Failed to check payment status');
            }

            return data.data;
        }

        showModal() {
            if (this.modal) {
                this.closeModal(true);
            }

            this.modal = this.createModalElement();
            document.body.appendChild(this.modal);

            setTimeout(() => {
                this.modal.classList.add('tron-payment-modal--visible');
            }, 10);

            document.body.style.overflow = 'hidden';

            this.escapeHandler = (e) => {
                if (e.key === 'Escape') {
                    this.closeModal();
                }
            };
            document.addEventListener('keydown', this.escapeHandler);
        }

        createModalElement() {
            const modal = document.createElement('div');
            modal.className = 'tron-payment-modal';
            modal.innerHTML = this.getModalHTML();

            modal.querySelector('.tron-payment-close').addEventListener('click', () => {
                this.closeModal();
                if (this.currentPayment && this.currentPayment.onCancel) {
                    this.currentPayment.onCancel();
                }
            });

            modal.querySelector('.tron-payment-backdrop').addEventListener('click', () => {
                this.closeModal();
                if (this.currentPayment && this.currentPayment.onCancel) {
                    this.currentPayment.onCancel();
                }
            });

            modal.querySelector('.tron-payment-copy-address').addEventListener('click', () => {
                this.copyToClipboard(this.currentPayment.wallet_address);
            });

            modal.querySelector('.tron-payment-copy-amount').addEventListener('click', () => {
                this.copyToClipboard(this.currentPayment.amount_crypto);
            });

            return modal;
        }

        getModalHTML() {
            const payment = this.currentPayment;
            const theme = this.currentPayment.theme;

            return `
                <div class="tron-payment-backdrop"></div>
                <div class="tron-payment-container" style="
                    background-color: ${theme.backgroundColor};
                    color: ${theme.textColor || this.config.theme.textColor};
                    border-radius: ${theme.borderRadius};
                    font-family: ${theme.fontFamily};
                ">
                    <div class="tron-payment-header">
                        <h3 class="tron-payment-title">Complete Payment</h3>
                        <button class="tron-payment-close" type="button">&times;</button>
                    </div>

                    <div class="tron-payment-content">
                        <div class="tron-payment-info">
                            <div class="tron-payment-amount">
                                <span class="tron-payment-label">Amount to pay:</span>
                                <div class="tron-payment-value">
                                    <span class="tron-payment-crypto-amount">${payment.amount_crypto}</span>
                                    <span class="tron-payment-asset" style="color: ${theme.primaryColor};">${payment.asset}</span>
                                </div>
                                <div class="tron-payment-usd-amount">≈ $${payment.amount_usd}</div>
                            </div>

                            <div class="tron-payment-countdown">
                                <span class="tron-payment-label">Time remaining:</span>
                                <div class="tron-payment-timer" style="color: ${theme.warningColor || this.config.theme.warningColor};">
                                    <span id="tron-payment-countdown">10:00</span>
                                </div>
                            </div>
                        </div>

                        <div class="tron-payment-qr-section">
                            <div class="tron-payment-qr-container">
                                <div id="tron-payment-qr-code"></div>
                            </div>
                            <p class="tron-payment-qr-text">Scan QR code to pay</p>
                        </div>

                        <div class="tron-payment-address-section">
                            <span class="tron-payment-label">Or send to wallet address:</span>
                            <div class="tron-payment-address-container">
                                <div class="tron-payment-address">${payment.wallet_address}</div>
                                <button class="tron-payment-copy-address tron-payment-copy-btn" style="background-color: ${theme.primaryColor};">
                                    Copy
                                </button>
                            </div>
                        </div>

                        <div class="tron-payment-amount-section">
                            <span class="tron-payment-label">Exact amount:</span>
                            <div class="tron-payment-amount-container">
                                <div class="tron-payment-exact-amount">${payment.amount_crypto}</div>
                                <button class="tron-payment-copy-amount tron-payment-copy-btn" style="background-color: ${theme.primaryColor};">
                                    Copy
                                </button>
                            </div>
                        </div>

                        <div class="tron-payment-status">
                            <div class="tron-payment-status-indicator">
                                <div class="tron-payment-spinner"></div>
                                <span>Waiting for payment...</span>
                            </div>
                        </div>

                        <div class="tron-payment-instructions">
                            <p><strong>Important:</strong> Send the exact amount to complete the payment. Any difference may result in payment failure.</p>
                        </div>
                    </div>
                </div>
            `;
        }

        startPaymentMonitoring() {
            this.startCountdown();
            this.pollingTimer = setInterval(async () => {
                try {
                    const status = await this.checkPaymentStatus(this.currentPayment.transaction_id);
                    this.updatePaymentStatus(status);
                } catch (error) {
                    console.error('Error checking payment status:', error);
                }
            }, this.config.pollingInterval);

            this.generateQRCode();
        }

        updatePaymentStatus(payment) {
            if (payment.status === 'completed') {
                this.onPaymentSuccess(payment);
            } else if (payment.status === 'expired') {
                this.onPaymentExpired();
            }
        }

        onPaymentSuccess(payment) {
            this.stopMonitoring();

            const theme = this.currentPayment.theme || this.config.theme;
            const statusElement = this.modal.querySelector('.tron-payment-status');
            statusElement.innerHTML = `
                <div class="tron-payment-success" style="color: ${theme.successColor};">
                    <div class="tron-payment-checkmark">✓</div>
                    <span>Payment completed successfully!</span>
                </div>
            `;

            setTimeout(() => {
                this.closeModal(true);
                if (this.currentPayment && this.currentPayment.onSuccess) {
                    this.currentPayment.onSuccess(payment);
                }
            }, 3000);
        }

        onPaymentExpired() {
            this.stopMonitoring();

            const theme = this.currentPayment.theme || this.config.theme;
            const statusElement = this.modal.querySelector('.tron-payment-status');
            statusElement.innerHTML = `
                <div class="tron-payment-error" style="color: ${theme.errorColor};">
                    <span>Payment expired. Please try again.</span>
                </div>
            `;

            setTimeout(() => {
                this.closeModal(true);
                if (this.currentPayment && this.currentPayment.onError) {
                    this.currentPayment.onError(new Error('Payment expired'));
                }
            }, 3000);
        }

        startCountdown() {
            const expiresAt = new Date(this.currentPayment.expires_at);

            this.countdownTimer = setInterval(() => {
                const now = new Date();
                const timeLeft = expiresAt - now;

                if (timeLeft <= 0) {
                    this.onPaymentExpired();
                    return;
                }

                const minutes = Math.floor(timeLeft / 60000);
                const seconds = Math.floor((timeLeft % 60000) / 1000);

                const countdownElement = this.modal.querySelector('#tron-payment-countdown');
                if (countdownElement) {
                    countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                }
            }, 1000);
        }

        generateQRCode() {
            const qrData = `tron:${this.currentPayment.wallet_address}?amount=${this.currentPayment.amount_crypto}&token=${this.currentPayment.asset}`;
            const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=${this.config.qrCodeSize}x${this.config.qrCodeSize}&data=${encodeURIComponent(qrData)}`;

            const qrContainer = this.modal.querySelector('#tron-payment-qr-code');
            if (qrContainer) {
                qrContainer.innerHTML = `<img src="${qrCodeUrl}" alt="Payment QR Code" style="max-width: 100%; height: auto; border-radius: 8px;">`;
            }
        }

        async copyToClipboard(text) {
            try {
                await navigator.clipboard.writeText(text);
                this.showToast('Copied to clipboard!');
            } catch (error) {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                this.showToast('Copied to clipboard!');
            }
        }

        showToast(message) {
            const existingToast = document.querySelector('.tron-payment-toast');
            if (existingToast) {
                existingToast.remove();
            }

            const theme = (this.currentPayment && this.currentPayment.theme) || this.config.theme;
            const toast = document.createElement('div');
            toast.className = 'tron-payment-toast';
            toast.textContent = message;
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background-color: ${theme.successColor};
                color: white;
                padding: 12px 20px;
                border-radius: 6px;
                z-index: 10001;
                font-family: ${theme.fontFamily};
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                transform: translateX(100%);
                transition: transform 0.3s ease;
            `;

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.transform = 'translateX(0)';
            }, 10);

            setTimeout(() => {
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }, 2000);
        }

        stopMonitoring() {
            if (this.pollingTimer) {
                clearInterval(this.pollingTimer);
                this.pollingTimer = null;
            }

            if (this.countdownTimer) {
                clearInterval(this.countdownTimer);
                this.countdownTimer = null;
            }
        }

        closeModal(skipConfirmation = false) {
            if (!skipConfirmation && this.currentPayment && this.currentPayment.status === 'pending') {
                const confirmed = confirm('Are you sure you want to close the payment? The payment will be cancelled.');
                if (!confirmed) {
                    return;
                }
            }
            if (this.modal) {
                this.modal.classList.remove('tron-payment-modal--visible');

                setTimeout(() => {
                    if (this.modal && this.modal.parentNode) {
                        this.modal.parentNode.removeChild(this.modal);
                    }
                    this.modal = null;
                }, 300);

                document.body.style.overflow = '';
            }

            if (this.escapeHandler) {
                document.removeEventListener('keydown', this.escapeHandler);
                this.escapeHandler = null;
            }

            this.stopMonitoring();
            this.currentPayment = null;
        }

        detectApiUrl() {
            const script = document.currentScript ||
                document.querySelector('script[src*="tron-payment"]') ||
                document.querySelector('script[src*="/widget/"]');

            if (script && script.src) {
                const url = new URL(script.src);
                return `${url.protocol}//${url.host}/store`;
            }

            return window.location.origin + '/store';
        }

        injectStyles() {
            if (document.querySelector('#tron-payment-styles')) {
                return;
            }

            const style = document.createElement('style');
            style.id = 'tron-payment-styles';
            style.textContent = this.getStyles();
            document.head.appendChild(style);
        }

        getStyles() {
            return `
                .tron-payment-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 10000;
                    opacity: 0;
                    visibility: hidden;
                    transition: opacity 0.3s ease, visibility 0.3s ease;
                }

                .tron-payment-modal--visible {
                    opacity: 1;
                    visibility: visible;
                }

                .tron-payment-backdrop {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.5);
                    backdrop-filter: blur(4px);
                }

                .tron-payment-container {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    width: 90%;
                    max-width: 480px;
                    max-height: 90vh;
                    overflow-y: auto;
                    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
                    border: 1px solid rgba(229, 231, 235, 0.5);
                }

                .tron-payment-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 10px 20px;
                    border-bottom: 1px solid rgba(229, 231, 235, 0.5);
                }

                .tron-payment-title {
                    margin: 0;
                    font-size: 20px;
                    font-weight: 600;
                }

                .tron-payment-close {
                    background: none;
                    border: none;
                    font-size: 24px;
                    cursor: pointer;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 4px;
                    transition: background-color 0.2s;
                }

                .tron-payment-close:hover {
                    background-color: #f3f4f6;
                }

                .tron-payment-content {
                    padding: 20px;
                }

                .tron-payment-info {
                    margin-bottom: 15px;
                }

                .tron-payment-amount {
                    text-align: center;
                    margin-bottom: 10px;
                }

                .tron-payment-label {
                    display: block;
                    font-size: 14px;
                    color: #6b7280;
                    margin-bottom: 5px;
                }

                .tron-payment-value {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    margin-bottom: 3px;
                }

                .tron-payment-crypto-amount {
                    font-size: 24px;
                    font-weight: 700;
                }

                .tron-payment-asset {
                    font-size: 18px;
                    font-weight: 600;
                    padding: 2px 8px;
                    background-color: #f3f4f6;
                    border-radius: 4px;
                }

                .tron-payment-usd-amount {
                    font-size: 14px;
                    color: #6b7280;
                }

                .tron-payment-countdown {
                    text-align: center;
                    padding: 10px;
                    background-color: #fef3c7;
                    border-radius: 8px;
                }

                .tron-payment-timer {
                    font-size: 18px;
                    font-weight: 600;
                }

                .tron-payment-qr-section {
                    text-align: center;
                    margin-bottom: 10px;
                }

                .tron-payment-qr-container {
                    display: inline-block;
                    padding: 16px;
                    background-color: white;
                    border-radius: 12px;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                    margin-bottom: 5px;
                }

                .tron-payment-qr-text {
                    margin: 0;
                    font-size: 14px;
                    color: #6b7280;
                }

                .tron-payment-address-section,
                .tron-payment-amount-section {
                    margin-bottom: 10px;
                }

                .tron-payment-address-container,
                .tron-payment-amount-container {
                    display: flex;
                    gap: 8px;
                    margin-top: 5px;
                }

                .tron-payment-address,
                .tron-payment-exact-amount {
                    flex: 1;
                    padding: 5px 10px;
                    color: #000000;
                    background-color: #f9fafb;
                    border: 1px solid #e5e7eb;
                    border-radius: 6px;
                    font-family: monospace;
                    font-size: 14px;
                    line-height: 30px;
                    word-break: break-all;
                }

                .tron-payment-copy-btn {
                    padding: 5px 15px;
                    color: white;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    font-weight: 500;
                    transition: opacity 0.2s;
                    white-space: nowrap;
                }

                .tron-payment-copy-btn:hover {
                    opacity: 0.9;
                }

                .tron-payment-status {
                    text-align: center;
                    margin: 20px 0 10px;
                }

                .tron-payment-status-indicator {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    padding: 12px;
                    color: #6b7280;
                    background-color: #f0f9ff;
                    border: 1px solid #e0f2fe;
                    border-radius: 8px;
                }

                .tron-payment-spinner {
                    width: 16px;
                    height: 16px;
                    border: 2px solid #e5e7eb;
                    border-top: 2px solid #3b82f6;
                    border-radius: 50%;
                    animation: tron-payment-spin 1s linear infinite;
                }

                .tron-payment-success {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    padding: 12px;
                    background-color: #f0fdf4;
                    border: 1px solid #bbf7d0;
                    border-radius: 8px;
                }

                .tron-payment-checkmark {
                    width: 20px;
                    height: 20px;
                    background-color: #10b981;
                    color: white;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                }

                .tron-payment-error {
                    padding: 12px;
                    background-color: #fef2f2;
                    border: 1px solid #fecaca;
                    border-radius: 8px;
                }

                .tron-payment-instructions {
                    font-size: 14px;
                    color: #6b7280;
                    background-color: #f9fafb;
                    padding: 12px;
                    border-radius: 6px;
                    border-left: 4px solid #3b82f6;
                }

                @keyframes tron-payment-spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }

                @media (max-width: 640px) {
                    .tron-payment-container {
                        width: 95%;
                    }

                    .tron-payment-content {
                        padding: 16px;
                    }

                    .tron-payment-address,
                    .tron-payment-exact-amount {
                        font-size: 13px;
                    }
                }
            `;
        }

        getVersion() {
            return this.version;
        }

        getBuildInfo() {
            return {
                version: this.version,
                buildTime: new Date(this.buildTime * 1000).toISOString(),
                buildTimestamp: this.buildTime
            };
        }

        isUsingStoreKey() {
            return !!this.config.storeKey;
        }

        getConfig() {
            return {
                version: this.version,
                buildTime: this.buildTime,
                authType: this.config.storeKey ? 'store_key' : 'partner_key',
                apiUrl: this.config.apiUrl,
                hasTheme: !!this.config.theme
            };
        }
    }

    window.TronPayment = new TronPaymentWidget();

})(window);
