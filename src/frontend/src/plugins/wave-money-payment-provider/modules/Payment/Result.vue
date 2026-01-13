<template>
  <div class="welcome">
    <div class="content">
      <div class="title">
        <span class="title highlight">MicroPowerManager</span>
      </div>

      <span>Dear customer,</span>
      <br />
      <p class="cloud-description">
        Your payment has been received. You will be informed about the payment
        details as soon as possible.
      </p>
    </div>
  </div>
</template>

<script>
export default {
  name: "Result",
}
</script>

<style scoped>
.welcome {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  max-width: 720px;
  text-align: center;
  margin-top: 18rem;
}

.title {
  font-size: 2rem;
  font-weight: bold;
  margin-bottom: 1rem;
}
</style>
