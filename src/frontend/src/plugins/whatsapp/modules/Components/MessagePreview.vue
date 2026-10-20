<template>
  <div class="phone-preview">
    <div class="phone-header">
      <md-icon>arrow_back</md-icon>
      <span>WhatsApp</span>
    </div>
    <div class="conversation">
      <div class="message-bubble">
        {{ renderedMessage }}
        <span class="message-time">10:42 ✓✓</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "MessagePreview",
  props: {
    message: {
      type: String,
      required: true,
    },
  },
  computed: {
    renderedMessage() {
      return this.message
        .replace("{{customer_name}}", "Amara")
        .replace("{{payment_amount}}", "MT 1,250")
        .replace("{{payment_date}}", "8 October 2026")
        .replace("{{transaction_id}}", "PAY-10428")
        .replace("{{token}}", "1234 5678 9012")
        .replace("{{balance}}", "MT 245")
    },
  },
}
</script>

<style lang="scss" scoped>
.phone-preview {
  background: #e5ddd5;
  border: 8px solid #263238;
  border-radius: 24px;
  min-height: 280px;
  overflow: hidden;
}

.phone-header {
  align-items: center;
  background: #075e54;
  color: white;
  display: flex;
  gap: 10px;
  padding: 12px;
}

.phone-header .md-icon {
  color: white;
}

.conversation {
  display: flex;
  padding: 24px 12px;
}

.message-bubble {
  background: #dcf8c6;
  border-radius: 8px;
  box-shadow: 0 1px 1px rgb(0 0 0 / 15%);
  color: #263238;
  line-height: 1.45;
  max-width: 90%;
  padding: 10px 12px 20px;
  position: relative;
  white-space: pre-wrap;
}

.message-time {
  bottom: 5px;
  color: #607d8b;
  font-size: 0.68rem;
  position: absolute;
  right: 8px;
}
</style>
