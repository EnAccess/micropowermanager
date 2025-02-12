<template>
  <md-snackbar
    :md-active.sync="showSnackbar"
    :md-duration="duration"
    :class="snackbarClass"
  >
    {{ message }}
    <md-button class="md-primary close-button" @click="showSnackbar = false">
      Close
    </md-button>
  </md-snackbar>
</template>

<script>
import { EventBus } from "@/shared/eventbus"

export default {
  data() {
    return {
      showSnackbar: false,
      message: "",
      duration: 4000,
      snackbarClass: "",
    }
  },
  mounted() {
    EventBus.$on("show-snackbar", this.show)
  },
  beforeDestroy() {
    EventBus.$off("show-snackbar", this.show)
  },
  methods: {
    show({ type, message }) {
      this.message = message
      this.snackbarClass = `md-${type}`
      this.showSnackbar = true
    },
  },
}
</script>

<style scoped>
.md-snackbar.md-success {
  background-color: #4caf50 !important;
}

.md-snackbar.md-error {
  background-color: #f44336 !important;
}
.close-button {
  font-size: 16px;
  padding: 8px 16px;
  background-color: white !important;
  color: #4caf50 !important;
  border-radius: 4px;
  font-weight: bold;
  text-transform: uppercase;
  box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.2);
}

.md-snackbar.md-error .close-button {
  color: #f44336 !important;
}
</style>
