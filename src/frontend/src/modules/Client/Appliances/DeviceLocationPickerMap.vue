<template>
  <div class="mpm-map device-location-map"></div>
</template>

<script>
import { mapComponent } from "@/mixins/mapComponent.js"
import { notify } from "@/mixins/notify.js"

export default {
  name: "DeviceLocationPickerMap",
  mixins: [mapComponent, notify],
  props: {
    initialLocation: {
      type: Array,
      required: false,
      default: null,
    },
    markerIcon: {
      type: String,
      default: null,
    },
  },
  mounted() {
    this.onMapClick(({ lat, lng }) => this.selectLocation([lat, lng]))
    if (this.hasInitialLocation()) {
      this.setMarker(this.initialLocation)
    } else {
      this.engine.showStartView()
    }
  },
  watch: {
    initialLocation(newLocation) {
      if (newLocation && newLocation.length === 2) {
        this.setMarker(newLocation)
      } else {
        this.removeExistingMarkers()
      }
    },
  },
  methods: {
    hasInitialLocation() {
      return this.initialLocation && this.initialLocation.length === 2
    },
    selectLocation(location) {
      this.setMarker(location)
      this.$emit("location-selected", {
        lat: Number(location[0].toFixed(5)),
        lon: Number(location[1].toFixed(5)),
      })
    },
    setMarker(location) {
      this.removeExistingMarkers()
      this.engine.addMarker({
        lat: location[0],
        lon: location[1],
        iconUrl: this.markerIcon,
        draggable: true,
        onDragEnd: ({ lat, lon }) =>
          this.$emit("location-selected", {
            lat: Number(lat.toFixed(5)),
            lon: Number(lon.toFixed(5)),
          }),
      })
    },
  },
}
</script>

<style scoped lang="scss">
.device-location-map {
  width: 100%;
  height: 450px;
}
</style>
