<template>
  <div id="map"></div>
</template>

<script>
import { ICON_OPTIONS, ICONS, MARKER_TYPE } from "@/services/MappingService"
import { notify, sharedMap } from "@/mixins"

export default {
  name: "DashboardMap",
  mixins: [sharedMap, notify],
  methods: {
    drawClusters() {
      this.editableLayer.clearLayers()
      const geoData = this.mappingService.geoData

      // Handle both single feature and array of features
      const features = Array.isArray(geoData) ? geoData : [geoData]

      // Create FeatureCollection with cluster properties
      const featureCollection = {
        type: "FeatureCollection",
        features: features.map((feature) => {
          if (feature.type !== "Feature") {
            throw new Error("Expected GeoJSON Feature, got: " + feature.type)
          }
          return {
            ...feature,
            properties: {
              ...feature.properties,
              clusterId: feature.properties?.clusterId || -1,
              clusterDisplayName:
                feature.properties?.clusterDisplayName ||
                feature.properties?.display_name ||
                feature.properties?.name ||
                "",
            },
          }
        }),
      }

      const editableLayer = this.editableLayer
      const geoDataItems = this.geoDataItems
      const parent = this

      const drawnCluster = L.geoJSON(featureCollection, {
        style: (feature) => {
          const displayName =
            feature.properties?.clusterDisplayName ||
            feature.properties?.display_name ||
            feature.properties?.name ||
            ""
          const polygonColor = this.mappingService.strToHex(displayName)
          return { fillColor: polygonColor, color: polygonColor }
        },
        onEachFeature: function (feature, layer) {
          const clusterId = feature.properties?.clusterId || -1
          const displayName =
            feature.properties?.clusterDisplayName ||
            feature.properties?.display_name ||
            feature.properties?.name ||
            ""

          if (feature.geometry.type === "Polygon" && clusterId !== -1) {
            layer.on("click", () => {
              parent.routeToDetail("/clusters", clusterId)
            })
          }

          editableLayer.addLayer(layer)
          layer.bindTooltip("<strong>Cluster:</strong> " + displayName, {
            sticky: true,
            offset: [10, 10],
          })

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
    setMiniGridMarkers() {
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
          let tooltip = "<strong>Mini Grid:</strong> " + markingInfo.name
          if (markingInfo.clusterId !== undefined) {
            tooltip +=
              "<br><strong>Cluster:</strong> " + markingInfo.clusterDisplayName
          }
          miniGridMarker.bindTooltip(tooltip, {
            sticky: true,
            offset: [10, 10],
          })

          const parent = this
          miniGridMarker.on("click", function () {
            parent.routeToDetail("/dashboards/mini-grid", markingInfo.id)
          })

          miniGridMarker.addTo(this.markersLayer)
        })

      this.map.addLayer(this.markersLayer)
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
