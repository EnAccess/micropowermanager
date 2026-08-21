<template>
  <div class="spinner" role="status" aria-live="polite">
    <span class="spinner__circle"></span>
  </div>
</template>

<script>
export default {
  name: "Spinner",
}
</script>

<style lang="scss" scoped>
.spinner {
  display: flex;
  justify-content: center;
  padding: 32px 0;
}

.spinner__circle {
  width: 26px;
  height: 26px;
  border: 2px solid $ops-card-border;
  border-top-color: $brand-primary;
  border-radius: 50%;
  animation: spinner-rotate 0.9s linear infinite;
}

@keyframes spinner-rotate {
  to {
    transform: rotate(360deg);
  }
}
</style>
