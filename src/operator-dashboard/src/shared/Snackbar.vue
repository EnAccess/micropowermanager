<template>
  <transition name="snackbar">
    <div v-if="visible" class="snackbar" :class="`snackbar--${type}`">
      {{ message }}
    </div>
  </transition>
</template>

<script>
import { EventBus } from "@/shared/eventbus.js"

const VISIBLE_MILLISECONDS = 4000

export default {
  name: "Snackbar",
  data() {
    return {
      visible: false,
      message: "",
      type: "success",
      timeout: null,
    }
  },
  mounted() {
    EventBus.$on("show-snackbar", this.show)
  },
  beforeDestroy() {
    EventBus.$off("show-snackbar", this.show)
    clearTimeout(this.timeout)
  },
  methods: {
    show({ type, message }) {
      this.type = type
      this.message = message
      this.visible = true

      clearTimeout(this.timeout)
      this.timeout = setTimeout(() => {
        this.visible = false
      }, VISIBLE_MILLISECONDS)
    },
  },
}
</script>

<style lang="scss" scoped>
.snackbar {
  position: fixed;
  left: 50%;
  bottom: 24px;
  transform: translateX(-50%);
  z-index: 40;
  padding: 12px 20px;
  border-radius: $ops-radius-control;
  color: $brand-white;
  font-size: 13.5px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}

.snackbar--success {
  background: $brand-accent-dark;
}

.snackbar--error {
  background: #c0392b;
}

.snackbar-enter-active,
.snackbar-leave-active {
  transition: opacity 0.2s ease;
}

.snackbar-enter,
.snackbar-leave-to {
  opacity: 0;
}
</style>
