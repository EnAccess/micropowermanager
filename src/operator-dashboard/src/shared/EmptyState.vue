<template>
  <div class="empty-state">
    <span class="material-icons empty-state__icon">{{ icon }}</span>
    <p class="empty-state__description">{{ description }}</p>
    <slot></slot>
  </div>
</template>

<script>
export default {
  name: "EmptyState",
  props: {
    icon: {
      type: String,
      default: "inbox",
    },
    description: {
      type: String,
      required: true,
    },
  },
}
</script>

<style lang="scss" scoped>
.empty-state {
  padding: 36px 24px;
  text-align: center;
}

.empty-state__icon {
  font-size: 32px;
  color: $ops-text-faint;
}

.empty-state__description {
  margin: 8px 0 12px;
  font-size: 13.5px;
  font-weight: 300;
  color: $ops-text-muted;
}
</style>
