<template>
  <div class="device-location-map">
    <div id="map"></div>
  </div>
</template>

<script>
import { sharedMap, notify } from "@/mixins"
import { ICON_OPTIONS } from "@/services/MappingService"
import L from "leaflet"

export default {
  name: "DeviceLocationPickerMap",
  mixins: [sharedMap, notify],
  props: {
    initialLocation: {
      type: Array,
      required: false,
      default: null,
    },
  },
  mounted() {
    this.map.on("draw:created", this.onDrawCreated)
    this.map.on("draw:deleted", this.onDrawDeleted)
    if (this.initialLocation && this.initialLocation.length === 2) {
      this.setMarker(this.initialLocation)
      this.map.setView(this.initialLocation, this.zoom)
    }
  },
  beforeDestroy() {
    this.map.off("draw:created", this.onDrawCreated)
    this.map.off("draw:deleted", this.onDrawDeleted)
  },
  watch: {
    initialLocation(newLocation) {
      if (newLocation && newLocation.length === 2) {
        this.setMarker(newLocation)
        this.map.setView(newLocation, this.zoom)
      } else {
        this.clearMarkers()
      }
    },
  },
  methods: {
    onDrawCreated(event) {
      const layer = event.layer
      this.clearMarkers()
      this.editableLayer.addLayer(layer)
      const { lat, lng } = layer.getLatLng()
      this.$emit("location-selected", {
        lat: Number(lat.toFixed(5)),
        lon: Number(lng.toFixed(5)),
      })
    },
    onDrawDeleted() {
      this.clearMarkers()
      this.$emit("location-cleared")
    },
    setMarker(location) {
      this.clearMarkers()
      const markerIcon = L.icon({
        ...ICON_OPTIONS,
        iconUrl: this.mappingService.markerUrl || this.defaultMarkerIconUrl,
      })
      const marker = L.marker(location, {
        icon: markerIcon,
        draggable: false,
      })
      marker.addTo(this.editableLayer)
    },
    clearMarkers() {
      const layers = this.editableLayer.getLayers()
      layers.forEach((layer) => {
        this.editableLayer.removeLayer(layer)
      })
    },
  },
}
</script>

<style scoped>
#map {
  width: 100%;
  height: 450px;
}
</style>
