<template>
  <div>
      <component :is="layout">
      <router-view/>
      <notifications group="notify" position="bottom right" ></notifications>

    </component>
  </div>
</template>

<script>
const defaultLayout = 'default'
export default {
    name:'App.vue',
    computed: {
        layout() {
            if (this.$route.meta.layout === defaultLayout) {
                return defaultLayout
            }

            return 'div'
        }
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