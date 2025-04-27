<template>
  <widget
    :title="$tc('words.location')"
    color="green"
    :subscriber="'shs-location'"
  >
    <client-map
      :mappingService="mappingService"
      ref="mapRef"
      :edit="false"
      :zoom="12"
    />
  </widget>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import ClientMap from "@/modules/Map/ClientMap.vue"
import { MappingService, MARKER_TYPE } from "@/services/MappingService"

export default {
  name: "Location",
  components: {
    Widget,
    ClientMap,
  },
  props: {
    device: {
      type: Object,
      required: true,
    },
    serialNumber: {
      type: String,
      required: true,
    },
    id: {
      type: [String, Number],
      required: true,
    },
  },
  data() {
    return {
      mappingService: new MappingService(),
    }
  },
  mounted() {
    this.setMapData()
    this.$emit("widget-loaded", "location")
  },
  methods: {
    setMapData() {
      if (
        this.device.address &&
        this.device.address.geo &&
        this.device.address.geo.points
      ) {
        const points = this.device.address.geo.points.split(",")
        if (points.length === 2) {
          const lat = parseFloat(points[0])
          const lon = parseFloat(points[1])

          const markingInfo = {
            id: this.id,
            name: this.serialNumber,
            serialNumber: this.serialNumber,
            lat: lat,
            lon: lon,
            deviceType: "solar_home_system",
            markerType: MARKER_TYPE.SHS,
          }

          this.mappingService.setCenter([lat, lon])
          this.mappingService.setMarkingInfos([markingInfo])

          this.$nextTick(() => {
            if (this.$refs.mapRef) {
              this.$refs.mapRef.setDeviceMarkers()
            }
          })
        }
      }
    },
  },
}
</script>
