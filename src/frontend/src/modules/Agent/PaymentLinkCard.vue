<template>
  <md-card class="payment-link-card">
    <md-card-header>
      <div class="md-title">{{ $tc("phrases.paymentLinkGenerated") }}</div>
      <div class="md-subhead">{{ $tc("phrases.shareWithCustomer") }}</div>
    </md-card-header>
    <md-card-content>
      <!-- Payment Details -->
      <div class="payment-details">
        <div class="detail-row">
          <span class="detail-label">{{ $tc("words.customer") }}:</span>
          <span class="detail-value">
            {{ customer?.name }} {{ customer?.surname }}
          </span>
        </div>
        <div class="detail-row">
          <span class="detail-label">{{ $tc("words.amount") }}:</span>
          <span class="detail-value amount">
            {{ formatCurrency(payment.amount, payment.currency) }}
          </span>
        </div>
        <div class="detail-row">
          <span class="detail-label">{{ $tc("words.reference") }}:</span>
          <span class="detail-value reference">
            {{ payment.reference || payment.reference_id }}
          </span>
        </div>
        <div class="detail-row">
          <span class="detail-label">{{ $tc("words.status") }}:</span>
          <span class="detail-value">
            <md-chip :class="getStatusClass(payment.status)">
              {{ getStatusText(payment.status) }}
            </md-chip>
          </span>
        </div>
      </div>

      <!-- Payment Link Display -->
      <div class="payment-link-section">
        <div class="link-header">
          <md-icon>link</md-icon>
          <span>{{ $tc("phrases.paymentLink") }}</span>
        </div>
        <div class="link-container">
          <md-field>
            <md-input
              :value="paymentUrl"
              readonly
              id="payment-link-input"
              class="payment-link-input"
            />
            <md-button
              class="md-icon-button md-primary copy-button"
              @click="copyToClipboard"
              :title="$tc('phrases.copyLink')"
            >
              <md-icon>content_copy</md-icon>
            </md-button>
          </md-field>
        </div>
      </div>

      <!-- QR Code -->
      <div class="qr-code-section" v-if="showQRCode">
        <div class="qr-header">
          <md-icon>qr_code</md-icon>
          <span>{{ $tc("phrases.qrCode") }}</span>
        </div>
        <div class="qr-code-container">
          <canvas ref="qrCanvas" class="qr-canvas"></canvas>
          <p class="qr-instruction">
            {{ $tc("phrases.scanToPayInstruction") }}
          </p>
        </div>
      </div>

      <!-- Share Options -->
      <div class="share-options">
        <div class="share-header">
          <md-icon>share</md-icon>
          <span>{{ $tc("phrases.sharePaymentLink") }}</span>
        </div>
        <div class="share-buttons">
          <md-button
            class="md-raised share-btn sms-btn"
            @click="shareViaSMS"
            :disabled="!customerPhone"
          >
            <md-icon>sms</md-icon>
            {{ $tc("words.sms") }}
          </md-button>

          <md-button
            class="md-raised share-btn whatsapp-btn"
            @click="shareViaWhatsApp"
            :disabled="!customerPhone"
          >
            <md-icon>chat</md-icon>
            WhatsApp
          </md-button>

          <md-button
            class="md-raised share-btn email-btn"
            @click="shareViaEmail"
            :disabled="!customerEmail"
          >
            <md-icon>email</md-icon>
            {{ $tc("words.email") }}
          </md-button>

          <md-button
            class="md-raised share-btn copy-btn"
            @click="copyToClipboard"
          >
            <md-icon>content_copy</md-icon>
            {{ $tc("phrases.copyLink") }}
          </md-button>
        </div>
      </div>

      <!-- Payment Instructions -->
      <div class="payment-instructions">
        <md-icon class="instruction-icon">info</md-icon>
        <div class="instruction-text">
          <p>
            <strong>{{ $tc("phrases.instructionsForCustomer") }}:</strong>
          </p>
          <ol>
            <li>{{ $tc("phrases.clickPaymentLink") }}</li>
            <li>{{ $tc("phrases.enterCardDetails") }}</li>
            <li>{{ $tc("phrases.confirmPayment") }}</li>
            <li>{{ $tc("phrases.receiveEnergyCredit") }}</li>
          </ol>
        </div>
      </div>

      <!-- Verification Section -->
      <div class="verification-section" v-if="payment.status === 0">
        <md-button
          class="md-raised md-accent verify-btn"
          @click="verifyPayment"
          :disabled="verifying"
        >
          <md-progress-spinner v-if="verifying" :md-diameter="20" />
          <md-icon v-else>verified_user</md-icon>
          {{
            verifying ? $tc("phrases.verifying") : $tc("phrases.verifyPayment")
          }}
        </md-button>
      </div>
    </md-card-content>
  </md-card>
</template>

<script>
import QRCode from "qrcode"
import { notify } from "@/mixins/notify"
import { TransactionService as PaystackTransactionService } from "@/plugins/paystack-payment-provider/services/TransactionService"

export default {
  name: "PaymentLinkCard",
  mixins: [notify],
  props: {
    payment: {
      type: Object,
      required: true,
    },
    customer: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      showQRCode: true,
      verifying: false,
      paystackService: new PaystackTransactionService(),
    }
  },
  computed: {
    paymentUrl() {
      return this.payment.redirectionUrl || ""
    },
    customerPhone() {
      if (
        !this.customer ||
        !this.customer.addresses ||
        this.customer.addresses.length === 0
      ) {
        return null
      }
      return this.customer.addresses[0].phone
    },
    customerEmail() {
      if (
        !this.customer ||
        !this.customer.addresses ||
        this.customer.addresses.length === 0
      ) {
        return null
      }
      return this.customer.addresses[0].email
    },
  },
  mounted() {
    if (this.showQRCode && this.paymentUrl) {
      this.generateQRCode()
    }
  },
  watch: {
    paymentUrl() {
      if (this.showQRCode && this.paymentUrl) {
        this.generateQRCode()
      }
    },
  },
  methods: {
    async generateQRCode() {
      try {
        const canvas = this.$refs.qrCanvas
        if (canvas && this.paymentUrl) {
          await QRCode.toCanvas(canvas, this.paymentUrl, {
            width: 200,
            margin: 2,
          })
        }
      } catch (error) {
        console.error("Error generating QR code:", error)
      }
    },

    async copyToClipboard() {
      try {
        await navigator.clipboard.writeText(this.paymentUrl)
        this.alertNotify("success", "Payment link copied to clipboard!")
        this.$emit("link-shared", "clipboard")
      } catch (error) {
        console.error("Error copying to clipboard:", error)
        // Fallback for older browsers
        this.selectAndCopy()
      }
    },

    selectAndCopy() {
      const input = document.getElementById("payment-link-input")
      if (input) {
        input.select()
        document.execCommand("copy")
        this.alertNotify("success", "Payment link copied to clipboard!")
        this.$emit("link-shared", "clipboard")
      }
    },

    shareViaSMS() {
      if (!this.customerPhone) {
        this.alertNotify("warning", "Customer phone number not available")
        return
      }

      const message = this.createShareMessage()
      const smsUrl = `sms:${this.customerPhone}?body=${encodeURIComponent(message)}`

      try {
        window.open(smsUrl)
        this.$emit("link-shared", "sms")
      } catch (error) {
        console.error("Error opening SMS:", error)
        this.alertNotify("error", "Unable to open SMS application")
      }
    },

    shareViaWhatsApp() {
      if (!this.customerPhone) {
        this.alertNotify("warning", "Customer phone number not available")
        return
      }

      const message = this.createShareMessage()
      const whatsappUrl = `https://wa.me/${this.customerPhone.replace(/[^0-9]/g, "")}?text=${encodeURIComponent(message)}`

      try {
        window.open(whatsappUrl, "_blank")
        this.$emit("link-shared", "whatsapp")
      } catch (error) {
        console.error("Error opening WhatsApp:", error)
        this.alertNotify("error", "Unable to open WhatsApp")
      }
    },

    shareViaEmail() {
      if (!this.customerEmail) {
        this.alertNotify("warning", "Customer email not available")
        return
      }

      const subject = `Payment Link - ${this.formatCurrency(this.payment.amount, this.payment.currency)}`
      const message = this.createEmailMessage()
      const emailUrl = `mailto:${this.customerEmail}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(message)}`

      try {
        window.open(emailUrl)
        this.$emit("link-shared", "email")
      } catch (error) {
        console.error("Error opening email:", error)
        this.alertNotify("error", "Unable to open email application")
      }
    },

    createShareMessage() {
      return `Hi ${this.customer.name}, please use this secure link to complete your payment of ${this.formatCurrency(this.payment.amount, this.payment.currency)}: ${this.paymentUrl}`
    },

    createEmailMessage() {
      return `Dear ${this.customer.name} ${this.customer.surname},

Please use the secure payment link below to complete your payment:

Amount: ${this.formatCurrency(this.payment.amount, this.payment.currency)}
Reference: ${this.payment.reference || this.payment.reference_id}

Payment Link: ${this.paymentUrl}

Instructions:
1. Click on the payment link above
2. Enter your card details securely
3. Confirm the payment
4. Your energy credit will be applied automatically

If you have any questions, please contact your energy provider.

Thank you!`
    },

    async verifyPayment() {
      if (!this.payment.reference && !this.payment.paystack_reference) {
        this.alertNotify("error", "No payment reference found")
        return
      }

      try {
        this.verifying = true
        const reference =
          this.payment.paystack_reference || this.payment.reference
        await this.paystackService.verifyTransaction(reference)
        this.alertNotify("success", "Payment verification requested")

        // Emit event to refresh payment status
        this.$emit("payment-verified")
      } catch (error) {
        console.error("Error verifying payment:", error)
        this.alertNotify("error", "Failed to verify payment")
      } finally {
        this.verifying = false
      }
    },

    formatCurrency(amount, currency) {
      return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: currency || "NGN",
      }).format(amount)
    },

    getStatusClass(status) {
      switch (status) {
        case 0:
          return "md-primary"
        case 1:
          return "md-accent"
        case 2:
          return "md-warn"
        default:
          return ""
      }
    },

    getStatusText(status) {
      switch (status) {
        case 0:
          return "Pending"
        case 1:
          return "Completed"
        case 2:
          return "Failed"
        default:
          return "Unknown"
      }
    },
  },
}
</script>

<style scoped>
.payment-link-card {
  margin-bottom: 1rem;
}

.payment-details {
  margin-bottom: 1.5rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #eee;
}

.detail-label {
  font-weight: 600;
  color: #666;
}

.detail-value {
  text-align: right;
}

.amount {
  font-weight: 600;
  color: #2196f3;
  font-size: 1.1rem;
}

.reference {
  font-family: monospace;
  font-size: 0.9rem;
  background: #f5f5f5;
  padding: 0.2rem 0.4rem;
  border-radius: 4px;
}

.payment-link-section,
.qr-code-section,
.share-options {
  margin: 1.5rem 0;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.link-header,
.qr-header,
.share-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  font-weight: 600;
  color: #333;
}

.link-container {
  position: relative;
}

.payment-link-input {
  font-family: monospace !important;
  font-size: 0.9rem !important;
}

.copy-button {
  position: absolute;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
}

.qr-code-container {
  text-align: center;
}

.qr-canvas {
  border: 1px solid #ddd;
  border-radius: 8px;
}

.qr-instruction {
  margin-top: 0.5rem;
  font-size: 0.9rem;
  color: #666;
}

.share-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.share-btn {
  flex: 1;
  min-width: 120px;
}

.sms-btn {
  background-color: #28a745 !important;
  color: white !important;
}

.whatsapp-btn {
  background-color: #25d366 !important;
  color: white !important;
}

.email-btn {
  background-color: #dc3545 !important;
  color: white !important;
}

.copy-btn {
  background-color: #6c757d !important;
  color: white !important;
}

.payment-instructions {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  background: #e3f2fd;
  border-radius: 8px;
  margin: 1.5rem 0;
}

.instruction-icon {
  color: #1976d2;
  flex-shrink: 0;
}

.instruction-text {
  flex: 1;
}

.instruction-text p {
  margin: 0 0 0.5rem 0;
  color: #1976d2;
}

.instruction-text ol {
  margin: 0;
  padding-left: 1.2rem;
  color: #666;
}

.instruction-text li {
  margin-bottom: 0.3rem;
}

.verification-section {
  text-align: center;
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid #eee;
}

.verify-btn {
  min-width: 160px;
}

@media (max-width: 768px) {
  .detail-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }

  .detail-value {
    text-align: left;
  }

  .share-buttons {
    flex-direction: column;
  }

  .share-btn {
    min-width: auto;
    width: 100%;
  }

  .payment-instructions {
    flex-direction: column;
  }
}
</style>
