<template>
  <div class="device-location-map" :id="mapWrapperId">
    <div :id="mapContainerId"></div>
  </div>
</template>

<script>
import { sharedMap, notify } from "@/mixins"
import { ICON_OPTIONS } from "@/services/MappingService"
import defaultMarker from "leaflet/dist/images/marker-icon.png"
import markerShadow from "leaflet/dist/images/marker-shadow.png"
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
    mapContainerId: {
      type: String,
      default: "map",
    },
  },
  created() {
    this.ensureMarkerIcon()
  },
  mounted() {
    this.map.on("click", this.onMapClick)
    this.map.on("draw:created", this.onDrawCreated)
    this.map.on("draw:deleted", this.onDrawDeleted)
    if (this.initialLocation && this.initialLocation.length === 2) {
      this.setMarker(this.initialLocation)
      this.map.setView(this.initialLocation, this.zoom)
    }
  },
  beforeDestroy() {
    if (this.map) {
      this.map.off("click", this.onMapClick)
      this.map.off("draw:created", this.onDrawCreated)
      this.map.off("draw:deleted", this.onDrawDeleted)
      this.map.remove()
      this.map = null
    }
    this.resetLeafletContainer()
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
  computed: {
    mapWrapperId() {
      return `${this.mapContainerId}-wrapper`
    },
  },
  methods: {
    resetLeafletContainer() {
      if (typeof window === "undefined") return
      const container = L.DomUtil.get(this.mapContainerId)
      if (container && container._leaflet_id) {
        container._leaflet_id = null
      }
    },
    ensureMarkerIcon() {
      if (!this.mappingService.markerUrl) {
        this.mappingService.setMarkerUrl(defaultMarker)
      }
    },
    onMapClick(event) {
      const { lat, lng } = event.latlng
      this.setMarker([lat, lng])
      this.$emit("location-selected", {
        lat: Number(lat.toFixed(5)),
        lon: Number(lng.toFixed(5)),
      })
    },
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
      const iconUrl = this.mappingService.markerUrl || defaultMarker
      const markerIcon = L.icon({
        ...ICON_OPTIONS,
        iconUrl: iconUrl,
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowUrl: markerShadow,
        shadowSize: [41, 41],
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
.device-location-map {
  width: 100%;
}

.device-location-map div {
  width: 100%;
  height: 450px;
}

.leaflet-container img.leaflet-marker-icon,
.leaflet-container img.leaflet-marker-shadow {
  width: auto !important;
  height: auto !important;
}
</style>
