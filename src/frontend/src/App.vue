<template>
  <div>
    <component :is="layout">
      <router-view />
      <Snackbar ref="snackbar"></Snackbar>
    </component>
  </div>
</template>

<script>
const defaultLayout = "default"
export default {
  name: "App.vue",
  computed: {
    layout() {
      if (this.$route.meta.layout === defaultLayout) {
        return defaultLayout
      }

      return "div"
    },
  },
}
</script>
<style lang="css">
.md-dialog {
  z-index: 10 !important;
}

.md-content.md-tabs-content.md-theme-default {
  height: 100% !important;
}
</style>
