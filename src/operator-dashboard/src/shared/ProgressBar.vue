<template>
  <div
    class="progress-bar"
    :class="{ 'progress-bar--indeterminate': indeterminate }"
  >
    <div
      class="progress-bar__fill"
      :style="
        indeterminate ? null : { width: `${percentage}%`, background: color }
      "
    ></div>
  </div>
</template>

<script>
export default {
  name: "ProgressBar",
  props: {
    percentage: {
      type: Number,
      default: 0,
    },
    color: {
      type: String,
      default: null,
    },
    indeterminate: {
      type: Boolean,
      default: false,
    },
  },
}
</script>

<style lang="scss" scoped>
.progress-bar {
  position: relative;
  height: 7px;
  border-radius: $ops-radius-control;
  background: $ops-row-border;
  overflow: hidden;
}

.progress-bar__fill {
  height: 100%;
  border-radius: $ops-radius-control;
  background: $brand-primary;
}

.progress-bar--indeterminate {
  height: 3px;
  background: transparent;

  .progress-bar__fill {
    position: absolute;
    width: auto;
    animation: ops-indeterminate 1.4s ease-in-out infinite;
  }
}
</style>
