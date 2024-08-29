<template>
  <ul class="list-group">
    <li
      class="list-group-item city-list"
      v-for="city in cities"
      :key="city.id"
      :class="isCitySelected(city) ? 'active' : ''"
      @click="selectCity(city)"
    >
      <input type="checkbox" :checked="isCitySelected(city)" />

      {{ city.name }}
    </li>
  </ul>
</template>

<script>
export default {
  name: "VillageSelector",
  mounted() {
    this.getCityList()
  },

  data() {
    return {
      cities: null,
      selectedCitites: [],
    }
  },
  methods: {
    getCityList() {
      axios.get(resources.city.list).then((response) => {
        this.cities = response.data.data
      })
    },
    selectCity(city) {
      if (!this.isCitySelected(city)) {
        this.selectedCitites.push(city)
      } else {
        this.selectedCitites = this.selectedCitites.filter(
          (c) => c.id !== city.id,
        )
      }
      this.$emit("citySelected", this.selectedCitites)
    },
    isCitySelected(city) {
      let citySearch = this.selectedCitites.filter((c) => c.id === city.id)
      return citySearch.length === 1
    },
  },
}
</script>

<style scoped>
.city-list {
  cursor: pointer;
}
</style>
