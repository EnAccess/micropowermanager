<template>
  <div class="page-container">
    <div v-if="isLoading" class="loading-container">
      <md-progress-spinner md-mode="indeterminate"></md-progress-spinner>
    </div>
    <div v-else>
      <div class="md-layout md-gutter">
        <div class="md-layout-item md-size-50 md-small-size-100">
          <basic-details-widget
            :shs="shs"
            @widget-loaded="handleWidgetLoaded"
          />
        </div>

        <div
          class="md-layout-item md-size-50 md-small-size-100"
          v-if="hasPersonData"
        >
          <owner-widget
            :person="shs.device.person"
            @widget-loaded="handleWidgetLoaded"
          />
        </div>
      </div>

      <div class="md-layout md-gutter" v-if="hasAddressData">
        <div class="md-layout-item md-size-100">
          <location-widget
            :device="shs.device"
            :serialNumber="shs.serialNumber"
            :id="shs.id"
            @widget-loaded="handleWidgetLoaded"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import BasicDetailsWidget from "./BasicDetails.vue"
import OwnerWidget from "./Owner.vue"
import LocationWidget from "./Location.vue"
import { SolarHomeSystemService } from "@/services/SolarHomeSystemService"
import { notify } from "@/mixins"
import { EventBus } from "@/shared/eventbus"

export default {
  name: "SolarHomeSystemDetail",
  mixins: [notify],
  components: {
    BasicDetailsWidget,
    OwnerWidget,
    LocationWidget,
  },
  data() {
    return {
      solarHomeSystemService: new SolarHomeSystemService(),
      shs: {},
      isLoading: true,
      loadedWidgets: {
        details: false,
        owner: false,
        location: false,
      },
    }
  },
  computed: {
    hasPersonData() {
      return this.shs.device && this.shs.device.person
    },
    hasAddressData() {
      return this.shs.device && this.shs.device.address
    },
  },
  created() {
    const shsId = this.$route.params.id
    this.loadSolarHomeSystem(shsId)

    setTimeout(() => {
      if (this.isLoading) {
        console.warn("Loading timeout reached, forcing load completion")
        this.isLoading = false
      }
    }, 10000)
  },
  methods: {
    async loadSolarHomeSystem(id) {
      try {
        const result = await this.solarHomeSystemService.getSolarHomeSystem(id)
        this.shs = result

        console.log("Full SHS data:", this.shs)
        console.log("Device data:", this.shs.device)
        console.log("Person data:", this.shs.device?.person)
        console.log("Address data:", this.shs.device?.address)
        this.isLoading = false
      } catch (e) {
        console.error("Error loading SHS:", e)
        this.isLoading = false
        this.alertNotify(
          "error",
          e.message || this.$tc("phrases.errorLoadingShs"),
        )
      }
    },
    handleWidgetLoaded(widgetName) {
      console.log(`Widget ${widgetName} loaded`)
      this.loadedWidgets[widgetName] = true

      switch (widgetName) {
        case "details":
          EventBus.$emit("widgetContentLoaded", "shs-details", 1)
          break
        case "owner":
          EventBus.$emit("widgetContentLoaded", "shs-owner", 1)
          break
        case "location":
          EventBus.$emit("widgetContentLoaded", "shs-location", 1)
          break
      }
    },
  },
}
</script>

<style scoped>
.page-container {
  padding: 16px;
}

.loading-container {
  display: flex;
  justify-content: center;
  padding: 40px;
}
</style>
