<template>
  <div class="kpi-strip">
    <div v-for="item in items" :key="item.label" class="kpi-strip__cell">
      <div class="kpi-strip__label">{{ item.label }}</div>
      <div class="kpi-strip__value tabular">{{ item.value }}</div>
      <div v-if="item.hint" class="kpi-strip__hint" :class="hintClass(item)">
        {{ item.hint }}
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "KpiStrip",
  props: {
    items: {
      type: Array,
      required: true,
    },
  },
  methods: {
    hintClass(item) {
      return `kpi-strip__hint--${item.tone || "neutral"}`
    },
  },
}
</script>

<style lang="scss" scoped>
// One card divided by hairlines, rather than one card per figure.
.kpi-strip {
  background: $brand-white;
  border: 1px solid $ops-card-border;
  border-radius: $ops-radius-card;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
}

.kpi-strip__cell {
  padding: 20px 24px;
  min-width: 0;

  & + & {
    border-left: 1px solid $ops-card-border;
  }
}

.kpi-strip__label {
  font-size: 12px;
  font-weight: 300;
  color: $ops-text-muted;
}

.kpi-strip__value {
  font-size: 1.6rem;
  font-weight: 500;
  color: $ops-text-strong;
  line-height: 1.3;
  margin: 4px 0 2px;
}

.kpi-strip__hint {
  font-size: 12.5px;
  font-weight: 400;
}

.kpi-strip__hint--positive {
  color: $brand-accent-dark;
}

.kpi-strip__hint--negative {
  color: $ops-text-watch;
}

.kpi-strip__hint--neutral {
  color: $ops-text-muted;
}

@media screen and (max-width: 900px) {
  .kpi-strip {
    grid-template-columns: repeat(2, 1fr);
  }

  .kpi-strip__cell:nth-child(odd) {
    border-left: none;
  }

  .kpi-strip__cell:nth-child(n + 3) {
    border-top: 1px solid $ops-card-border;
  }
}

@media screen and (max-width: 540px) {
  .kpi-strip {
    grid-template-columns: 1fr;
  }

  .kpi-strip__cell + .kpi-strip__cell {
    border-left: none;
    border-top: 1px solid $ops-card-border;
  }
}
</style>
