<template>
  <div id="map"></div>
</template>

<script>
import { sharedMap, notify } from "@/mixins"
import { ICON_OPTIONS, ICONS, MARKER_TYPE } from "@/services/MappingService"

export default {
  name: "VillageMap",
  mixins: [sharedMap, notify],
  mounted() {
    const drawingLayer = this.editableLayer
    const map = this.map
    this.map.on("draw:created", (event) => {
      const type = event.layerType
      const layer = event.layer
      // const drawnLayers = drawingLayer.getLayers()
      let cluster = null
      map.eachLayer(function (layer) {
        if (layer.getBounds) {
          cluster = layer
        }
      })
      const bounds = cluster.getBounds()
      this.removeExistingMarkers()

      if (bounds.contains(layer._latlng)) {
        drawingLayer.addLayer(layer)
        const geoDataItem = {
          type: type,
          coordinates: layer._latlng,
        }
        this.$emit("locationSet", {
          error: undefined,
          geoDataItem: geoDataItem,
        })
      } else {
        const errorMessage =
          "Please position your village within the selected cluster boundaries."
        this.$emit("locationSet", {
          error: errorMessage,
          geoDataItem: undefined,
        })
      }
    })
  },
  methods: {
    drawCluster() {
      this.editableLayer.clearLayers()
      const geoData = this.mappingService.geoData

      // Handle both single feature and array of features
      const features = Array.isArray(geoData) ? geoData : [geoData]

      // Get the first feature (for Village, we typically have one cluster)
      const feature = features[0]

      if (feature.type !== "Feature") {
        throw new Error("Expected GeoJSON Feature, got: " + feature.type)
      }

      const featureCollection = {
        type: "FeatureCollection",
        features: [feature],
      }

      const polygonColor = this.mappingService.strToHex(
        feature.properties?.display_name || feature.properties?.name || "",
      )

      const editableLayer = this.editableLayer
      const geoDataItems = this.geoDataItems
      const parent = this

      const drawnCluster = L.geoJSON(featureCollection, {
        style: { fillColor: polygonColor, color: polygonColor },
        onEachFeature: function (feature, layer) {
          const clusterId = feature.properties?.clusterId || -1
          const displayName =
            feature.properties?.display_name || feature.properties?.name || ""

          if (feature.geometry.type === "Polygon" && clusterId !== -1) {
            layer.on("click", () => {
              parent.$router.push({
                path: "/clusters/" + clusterId,
              })
            })
          }

          editableLayer.addLayer(layer)

          const geoDataItem = {
            leaflet_id: layer._leaflet_id,
            type: "manual",
            geojson: feature.geometry,
            display_name: displayName,
            clusterId: clusterId,
          }
          geoDataItems.push(geoDataItem)
        },
      })

      const bounds = drawnCluster.getBounds()
      if (bounds.isValid()) {
        this.map.fitBounds(bounds)
      }
    },
    setMiniGridMarker() {
      this.mappingService.markingInfos
        .filter(
          (markingInfo) => markingInfo.markerType === MARKER_TYPE.MINI_GRID,
        )
        .map((markingInfo) => {
          const miniGridMarkerIcon = L.icon({
            ...ICON_OPTIONS,
            iconUrl: ICONS[markingInfo.markerType],
          })
          const miniGridMarker = L.marker([markingInfo.lat, markingInfo.lon], {
            icon: miniGridMarkerIcon,
          })
          miniGridMarker.bindTooltip("Mini Grid: " + markingInfo.name)
          const parent = this
          miniGridMarker.on("click", () => {
            parent.routeToDetail(markingInfo.id, markingInfo.name)
          })
          miniGridMarker.addTo(this.map)
        })
    },
    setVillageMarkerManually(location) {
      const editableLayers = this.editableLayer.getLayers()
      const polygon = editableLayers.find(
        (layer) => layer.feature && layer.feature.geometry.type === "Polygon",
      )
      const bounds = polygon.getBounds()

      if (!bounds.contains(location)) {
        const errorMessage =
          "Please position your village within the selected cluster boundaries."
        this.$emit("locationSet", {
          error: errorMessage,
          geoDataItem: undefined,
        })
      } else {
        this.removeExistingMarkers()

        const villageMarkerIcon = L.icon({
          ...ICON_OPTIONS,
          iconUrl: ICONS.VILLAGE,
        })

        const villageMarker = L.marker(location, {
          icon: villageMarkerIcon,
        })
        villageMarker.addTo(this.editableLayer)
      }
    },
  },
}
</script>

<style scoped>
#map {
  height: 100%;
  min-height: 500px;
  width: 100%;
}

.leaflet-draw-actions a {
  background: white !important;
}
</style>
