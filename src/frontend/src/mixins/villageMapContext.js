import { geoJsonToLatLon } from "@/Helpers/Utils.js"
import { MARKER_TYPE } from "@/services/MappingService.js"

// State and behavior shared by the add/edit village forms: manual lat/lon
// entry synced with a VillageMap marker (referenced as `villageMapRef`), and
// loading of the surrounding map context (parent cluster polygon + mini-grid
// marker). Consuming components must expose miniGridService, clusterService
// and mappingService instances in data, and use the notify mixin.
export const villageMapContext = {
  data() {
    return {
      cityLatLng: {
        lat: null,
        lon: null,
      },
    }
  },
  methods: {
    async loadVillageMapContext(miniGridId) {
      const miniGridWithGeoData =
        await this.miniGridService.getMiniGridGeoData(miniGridId)
      const location = geoJsonToLatLon(miniGridWithGeoData.location)
      if (location == null) {
        this.alertNotify("error", "Mini-Grid has no location")
        return null
      }

      const clusterGeoData = await this.clusterService.getClusterGeoLocation(
        miniGridWithGeoData.cluster_id,
      )
      const clusterFeature =
        this.mappingService.setClusterGeoData(clusterGeoData)
      if (!clusterFeature) {
        this.alertNotify("error", "Cluster has no geo data")
        return null
      }

      this.mappingService.setMarkingInfos([
        {
          id: miniGridWithGeoData.id,
          name: miniGridWithGeoData.name,
          serialNumber: null,
          lat: location.lat,
          lon: location.lon,
          deviceType: null,
          markerType: MARKER_TYPE.MINI_GRID,
        },
      ])

      return miniGridWithGeoData
    },
    villageLocationSet(data) {
      if (!data.error) {
        this.cityLatLng.lat = Number(
          data.geoDataItem.coordinates.lat.toFixed(5),
        )
        this.cityLatLng.lon = Number(
          data.geoDataItem.coordinates.lng.toFixed(5),
        )
      } else {
        this.cityLatLng.lat = null
        this.cityLatLng.lon = null
        this.$swal({
          type: "warning",
          text: data.error,
        })
      }
    },
    setPoints() {
      const location = [this.cityLatLng.lat, this.cityLatLng.lon]
      this.$refs.villageMapRef.setVillageMarkerManually(location)
    },
  },
}
