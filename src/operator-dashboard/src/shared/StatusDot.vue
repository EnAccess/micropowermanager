<template>
  <span
    class="status-dot"
    :class="`status-dot--${status}`"
    :style="sizeStyle"
  ></span>
</template>

<script>
export default {
  name: "StatusDot",
  props: {
    status: {
      type: String,
      required: true,
    },
    size: {
      type: Number,
      default: 8,
    },
  },
  computed: {
    sizeStyle() {
      return { width: `${this.size}px`, height: `${this.size}px` }
    },
  },
}
</script>

<style lang="scss" scoped>
.status-dot {
  display: inline-block;
  border-radius: 50%;
  flex: none;
}
</style>
