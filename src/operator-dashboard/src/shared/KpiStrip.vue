<template>
  <div class="kpi-strip">
    <div v-for="item in items" :key="item.label" class="kpi-box">
      <div class="kpi-box__tile" :class="`kpi-box__tile--${item.color}`">
        <span class="material-icons">{{ item.icon }}</span>
      </div>
      <div class="kpi-box__content">
        <div class="kpi-box__label">{{ item.label }}</div>
        <div class="kpi-box__value tabular">{{ item.value }}</div>
        <div v-if="item.hint" class="kpi-box__hint" :class="hintClass(item)">
          {{ item.hint }}
        </div>
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
      return `kpi-box__hint--${item.tone || "neutral"}`
    },
  },
}
</script>

<style lang="scss" scoped>
// Mirrors src/frontend/src/shared/Box.vue: the icon tile rises above the card.
.kpi-strip {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: $ops-gap;
  padding-top: 1.8rem;
}

.kpi-box {
  background: $brand-white;
  border-radius: $ops-radius-card;
  box-shadow: $ops-shadow-card;
  padding: 0 16px 16px 0;
  min-width: 0;
}

.kpi-box__tile {
  float: left;
  margin: -1.8rem 0 0 15px;
  padding: 30px;
  border-radius: 5px;
  box-shadow: $ops-shadow-kpi-tile;
  color: $brand-white;
  line-height: 0;

  .material-icons {
    font-size: 24px;
  }
}

@each $name, $gradient in $ops-kpi-tiles {
  .kpi-box__tile--#{$name} {
    background: $gradient;
  }
}

.kpi-box__content {
  text-align: end;
  padding-top: 16px;
}

.kpi-box__label {
  color: rgb(148, 148, 148);
  margin-bottom: 1rem;
}

.kpi-box__value {
  color: $ops-text-strong;
  font-size: $font-kpi;
  font-weight: bold;
  line-height: 1.2;
}

.kpi-box__hint {
  margin-top: 6px;
  font-size: $font-caption;
}

.kpi-box__hint--positive {
  color: $brand-accent-dark;
}

.kpi-box__hint--negative {
  color: $ops-text-watch;
}

.kpi-box__hint--neutral {
  color: $ops-text-muted;
}

@media screen and (max-width: #{$ops-breakpoint-desktop - 1px}) {
  .kpi-strip {
    grid-template-columns: repeat(2, 1fr);
    row-gap: calc(#{$ops-gap} + 1.8rem);
  }
}

@media screen and (max-width: $ops-breakpoint-phone) {
  .kpi-strip {
    grid-template-columns: 1fr;
  }
}
</style>
