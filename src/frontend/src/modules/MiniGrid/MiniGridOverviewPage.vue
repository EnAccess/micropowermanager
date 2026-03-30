<template>
  <div>
    <mini-grid-dashboard />
  </div>
</template>

<script>
import { MiniGridService } from "@/services/MiniGridService.js"
import Loader from "@/shared/Loader.vue"

export default {
  name: "MiniGridDashboardPage",
  components: { MiniGridDashboard },
}
</script>

<style scoped lang="scss"></style>
