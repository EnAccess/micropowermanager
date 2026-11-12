<template>
  <div class="mpm-map"></div>
</template>

<script>
import { mapComponent } from "@/mixins/mapComponent.js"
import { notify } from "@/mixins/notify.js"
import { ICONS, MARKER_TYPE, markerTooltip } from "@/services/MappingService.js"

export default {
  name: "ClientMap",
  mixins: [notify, mapComponent],
  mounted() {
    if (this.edit) {
      this.enableLocationEditing()
    }
  },
  methods: {
    setDeviceMarkers() {
      this.mappingService.markingInfos
        .filter(
          (markingInfo) =>
            markingInfo.markerType === MARKER_TYPE.METER ||
            markingInfo.markerType === MARKER_TYPE.SHS ||
            markingInfo.markerType === MARKER_TYPE.E_BIKE,
        )
        .forEach((markingInfo) => {
          this.engine.addMarker({
            lat: markingInfo.lat,
            lon: markingInfo.lon,
            iconUrl: ICONS[markingInfo.markerType],
            tooltipHtml: markerTooltip(markingInfo),
            editable: this.edit,
            data: { serialNumber: markingInfo.serialNumber },
            onClick: this.markerClickHandler(markingInfo),
          })
        })
    },
    markerClickHandler(markingInfo) {
      if (markingInfo.markerType === MARKER_TYPE.METER) {
        return () => this.routeToDetail("/meters", markingInfo.serialNumber)
      }
      if (markingInfo.markerType === MARKER_TYPE.E_BIKE) {
        return () =>
          this.routeToDetailWithQueryParam(
            "/e-bikes",
            "serialNumber",
            markingInfo.serialNumber,
          )
      }
      return undefined
    },
  },
}
</script>

<style scoped lang="scss">
.mpm-map {
  height: 100%;
  min-height: 500px;
  width: 100%;
}
</style>
