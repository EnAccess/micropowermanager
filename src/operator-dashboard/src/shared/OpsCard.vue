<template>
  <section class="ops-card">
    <header v-if="title" class="ops-card__header">
      <div class="ops-card__heading">
        <h2 class="ops-card__title">{{ title }}</h2>
        <p v-if="subtitle" class="ops-card__subtitle">{{ subtitle }}</p>
      </div>
      <div class="ops-card__actions">
        <slot name="actions"></slot>
      </div>
    </header>
    <slot></slot>
  </section>
</template>

<script>
export default {
  name: "OpsCard",
  props: {
    title: {
      type: String,
      default: null,
    },
    subtitle: {
      type: String,
      default: null,
    },
  },
}
</script>

<style lang="scss" scoped>
.ops-card {
  background: $brand-white;
  border: 1px solid $ops-card-border;
  border-radius: $ops-radius-card;
  overflow: hidden;
}

.ops-card__header {
  display: flex;
  align-items: baseline;
  gap: 10px;
  flex-wrap: wrap;
  padding: 20px 24px 12px;
}

.ops-card__heading {
  min-width: 0;
}

.ops-card__title {
  margin: 0;
  font-size: larger;
  font-weight: 300;
  color: $brand-primary-dark;
  line-height: 1.3;
}

.ops-card__subtitle {
  margin: 2px 0 0;
  font-size: 12px;
  font-weight: 300;
  color: $ops-text-muted;
}

.ops-card__actions {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 10px;
}
</style>
