<template>
  <ul class="list-group">
    <li
      class="list-group-item village-list"
      v-for="village in villages"
      :key="village.id"
      :class="isVillageSelected(village) ? 'active' : ''"
      @click="selectVillage(village)"
    >
      <input type="checkbox" :checked="isVillageSelected(village)" />

      {{ village.name }}
    </li>
  </ul>
</template>

<script>
import Client from "@/repositories/Client/AxiosClient.js"
import { resources } from "@/resources.js"

export default {
  name: "VillageSelector",
  mounted() {
    this.getVillageList()
  },

  data() {
    return {
      villages: null,
      selectedVillages: [],
    }
  },
  methods: {
    getVillageList() {
      Client.get(resources.village.list).then((response) => {
        this.villages = response.data.data
      })
    },
    selectVillage(village) {
      if (!this.isVillageSelected(village)) {
        this.selectedVillages.push(village)
      } else {
        this.selectedVillages = this.selectedVillages.filter(
          (c) => c.id !== village.id,
        )
      }
      this.$emit("villageSelected", this.selectedVillages)
    },
    isVillageSelected(village) {
      let villageSearch = this.selectedVillages.filter((c) => c.id === village.id)
      return villageSearch.length === 1
    },
  },
}
</script>

<style scoped lang="scss">
.village-list {
  cursor: pointer;
}
</style>
