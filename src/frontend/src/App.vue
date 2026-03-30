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
<style scoped lang="scss"></style>
