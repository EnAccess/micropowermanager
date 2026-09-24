<template>
  <section class="ops-card" :class="{ 'ops-card--titled': title }">
    <header
      v-if="title"
      class="ops-card__header"
      :class="`ops-card__header--${color}`"
    >
      <span class="material-icons ops-card__header-icon">list</span>
      <h2 class="ops-card__title">{{ title }}</h2>
      <div class="ops-card__actions">
        <slot name="actions"></slot>
      </div>
    </header>
    <div class="ops-card__body">
      <p v-if="subtitle" class="ops-card__subtitle">{{ subtitle }}</p>
      <slot></slot>
    </div>
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
    color: {
      type: String,
      default: "primary",
    },
  },
}
</script>

<style lang="scss" scoped>
// The header overlaps the top edge of the body, as src/frontend's Widget does,
// so a titled body needs padding to clear it.
.ops-card--titled .ops-card__body {
  padding-top: 30px;
}

.ops-card__header {
  position: relative;
  z-index: 2;
  top: 16px;
  left: 1%;
  width: 98%;
  margin-bottom: -10px;
  min-height: 48px;
  padding: 8px 16px;
  border-radius: $ops-radius-control;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px 8px;
  color: $brand-white;
}

@each $name, $spec in $ops-card-headers {
  .ops-card__header--#{$name} {
    background: map-get($spec, background);
    box-shadow: map-get($spec, shadow);
  }
}

.ops-card__header-icon {
  font-size: 24px;
}

.ops-card__title {
  margin: 0;
  font-size: $font-subheading;
  font-weight: 400;
  line-height: 24px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.ops-card__actions {
  margin-left: auto;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  max-width: 100%;
}

.ops-card__body {
  background: $brand-white;
  border-radius: $ops-radius-card;
  box-shadow: $ops-shadow-card;
  overflow: hidden;
}

.ops-card__subtitle {
  margin: 0;
  padding: 8px 16px 0;
  font-size: $font-caption;
  color: $ops-text-muted;
}
</style>
