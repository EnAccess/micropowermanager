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
      this.mappingService.geoData.map((geoData) => {
        const geoType = geoData.type
        const coordinatesClone = geoData.coordinates[0].reduce(
          (acc, coord) => {
            acc[0].push([coord[1], coord[0]])
            return acc
          },
          [[]],
        )
        const drawing = {
          type: "FeatureCollection",
          crs: {
            type: "name",
            properties: {
              name: "urn:ogc:def:crs:OGC:1.3:CRS84",
            },
          },
          features: [
            {
              type: "Feature",
              properties: {
                popupContent: geoData.clusterName,
                draw_type:
                  geoData.draw_type === undefined ? "set" : geoData.draw_type,
                selected:
                  geoData.selected === undefined ? false : geoData.selected,
                clusterId:
                  geoData.clusterId === undefined ? -1 : geoData.clusterId,
                clusterName:
                  geoData.clusterName === undefined ? "" : geoData.clusterName,
              },
              geometry: {
                type: geoType,
                coordinates: geoData.searched
                  ? geoData.coordinates
                  : coordinatesClone,
              },
            },
          ],
        }
        const polygonColor = this.mappingService.strToHex(
          geoData.clusterName || "default",
        )
        // "this"  cannot be used inside the L.geoJson function
        const editableLayer = this.editableLayer
        const geoDataItems = this.geoDataItems
        const parent = this
        const drawnCluster = L.geoJson(drawing, {
          style: { fillColor: polygonColor, color: polygonColor },
          onEachFeature: function (feature, layer) {
            const type = layer.feature.geometry.type
            const clusterId = layer.feature.properties.clusterId
            if (type === "Polygon" && clusterId !== -1) {
              layer.on("click", () => {
                parent.routeToDetail("/clusters", clusterId)
              })
            }

            editableLayer.addLayer(layer)
            layer.bindTooltip(
              "<strong>Cluster:</strong> " +
                layer.feature.properties.clusterName,
              { sticky: true, offset: [10, 10] },
            )
            const geoDataItem = {
              leaflet_id: layer._leaflet_id,
              type: geoData.type,
              coordinates:
                geoData.searched === true
                  ? coordinatesClone
                  : geoData.coordinates,
              searched: false,
              clusterName: geoData.clusterName,
              selected: feature.properties.selected,
              draw_type: feature.properties.draw_type,
              lat: geoData.lat,
              lon: geoData.lon,
            }
            geoDataItems.push(geoDataItem)
          },
        })
        const bounds = drawnCluster.getBounds()
        this.map.fitBounds(bounds)
      })
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
              "<br><strong>Cluster:</strong> " + markingInfo.clusterName
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
