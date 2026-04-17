<template>
  <div>
    <widget
      :id="'village-list'"
      :title="'Villages'"
      :subscriber="subscriber"
      :button="true"
      :button-text="$tc('menu.subMenu.addVillage')"
      color="primary"
      @widgetAction="goToAddVillage"
    >
      <md-table md-card style="margin-left: 0">
        <md-table-row>
          <md-table-head>{{ $tc("words.id") }}</md-table-head>
          <md-table-head>{{ $tc("words.name") }}</md-table-head>
          <md-table-head>{{ $tc("words.country") }}</md-table-head>
          <md-table-head>{{ $tc("words.miniGrid") }}</md-table-head>
          <md-table-head>{{ $tc("words.location") }}</md-table-head>
          <md-table-head>{{ $tc("phrases.lastUpdate") }}</md-table-head>
        </md-table-row>
        <md-table-row
          v-for="village in villages"
          :key="village.id"
          class="cursor-pointer"
          @click="goToVillageDetail(village.id)"
        >
          <md-table-cell>{{ village.id }}</md-table-cell>
          <md-table-cell>{{ village.name }}</md-table-cell>
          <md-table-cell>{{ getCountryName(village.country_id) }}</md-table-cell>
          <md-table-cell>{{ getMiniGridName(village.mini_grid_id) }}</md-table-cell>
          <md-table-cell>{{ village.location?.points || "-" }}</md-table-cell>
          <md-table-cell>{{ timeForTimeZone(village.updated_at) }}</md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
  </div>
</template>

<script>
import { notify } from "@/mixins/notify.js"
import { timing } from "@/mixins/timing.js"
import { CityService } from "@/services/CityService.js"
import CountryService from "@/services/CountryService.js"
import { MiniGridService } from "@/services/MiniGridService.js"
import { EventBus } from "@/shared/eventbus.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "Villages",
  mixins: [notify, timing],
  components: { Widget },
  data() {
    return {
      subscriber: "village-list",
      cityService: new CityService(),
      miniGridService: new MiniGridService(),
      countryService: new CountryService(),
      villages: [],
    }
  },
  mounted() {
    this.loadVillages()
  },
  methods: {
    async loadVillages() {
      try {
        const [villages] = await Promise.all([
          this.cityService.getCities(),
          this.miniGridService.getMiniGrids(),
          this.countryService.getCountries(),
        ])

        this.villages = [...villages].sort((a, b) =>
          (a.name || "").localeCompare(b.name || ""),
        )
        EventBus.$emit("widgetContentLoaded", this.subscriber, this.villages.length)
      } catch (e) {
        EventBus.$emit("widgetContentLoaded", this.subscriber, 0)
        this.alertNotify("error", e.message)
      }
    },
    getMiniGridName(miniGridId) {
      const miniGrid = this.miniGridService.list.find(
        (item) => item.id === miniGridId,
      )
      return miniGrid ? miniGrid.name : "-"
    },
    getCountryName(countryId) {
      const country = this.countryService.list.find((item) => item.id === countryId)
      return country ? country.name || country.country_name || "-" : "-"
    },
    goToVillageDetail(villageId) {
      this.$router.push(`/villages/${villageId}`)
    },
    goToAddVillage() {
      this.$router.push("/locations/add-village")
    },
  },
}
</script>

<style scoped lang="scss"></style>
