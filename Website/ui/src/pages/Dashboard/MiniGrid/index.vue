<template>
  <div>
    <Loader />
  </div>
</template>

<script>
import { MiniGridService } from "@/services/MiniGridService"
import Loader from "@/shared/Loader.vue"

export default {
  name: "MiniGridsPage",
  components: { Loader },
  created() {
    const miniGridId = this.$route.params.id
    if (miniGridId === undefined) {
      this.redirectToFirstMiniGrid()
    }
  },
  data() {
    return {
      miniGridService: new MiniGridService(),
    }
  },
  methods: {
    async redirectToFirstMiniGrid() {
      const miniGrids = await this.miniGridService.getMiniGrids()
      if (miniGrids.length > 0) {
        this.$router.replace("/dashboards/mini-grid/" + miniGrids[0].id)
      }
    },
  },
}
</script>

<style scoped></style>
