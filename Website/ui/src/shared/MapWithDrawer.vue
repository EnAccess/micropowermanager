<template>
  <div>
    <div style="margin-top: 20px" class="col-md-3">
      <div style="margin-bottom: 10px">
        <h5>Map Area</h5>
        <div class="row">
          <div class="col-sm-12">
            <button
              class="btn btn-sm"
              :class="drawCluster ? 'btn-warning' : 'btn-info'"
              @click="manualDrawwing"
              v-text="drawCluster ? 'Stop Drawing' : 'Manual Drawing'"
            ></button>
          </div>
          <div class="col-sm-12" style="margin-top: 5px">
            <button @click="getClusterGeoData" class="btn btn-sm btn-primary">
              Search Online
            </button>
          </div>
        </div>

        <div class="clearfix"></div>
      </div>
      <ul class="list-group" v-if="!drawCluster">
        <li
          class="list-group-item"
          style="cursor: pointer"
          :class="selectedLocationIndex === index ? 'active' : ''"
          v-for="(geo, index) in geoData"
          :key="index"
          @click="locationSelected(index, geo)"
        >
          {{ geo.display_name }}
        </li>
        <li
          v-if="drawnPolygons.initialized"
          @click="selectManual"
          class="list-group-item"
          :class="selectedLocationIndex === 'manual' ? 'active' : ''"
        >
          Manual drown
        </li>
      </ul>
      <ul class="list-group" v-else>
        <li
          class="list-group-item"
          v-for="(coordinate, index) in polygonCoordinates"
          :key="index"
        >
          <i
            @click="removeCoordinate(index)"
            class="fa fa-close"
            style="cursor: pointer"
          ></i>
          &nbsp; {{ coordinate[0] }}, {{ coordinate[1] }}
        </li>
      </ul>
    </div>
    <div class="col-md-9">
      <div
        class="map"
        id="map"
        style="height: 500px; border: 1px solid #ccc"
      ></div>
    </div>
  </div>
</template>

<script>
import { layerGroup } from "leaflet"

export default {
  name: "MapWithDrawer",
  components: {},
  props: {
    cluster_name: {
      type: String,
      required: true,
    },
    filtered_types: {
      type: Object,
      default: function () {
        return {}
      },
    },
  },
  mounted() {
    this.initMap()
  },
  data() {
    return {
      drawCluster: false,
      polygonCoordinates: [],
      polygon: null,
      drawnPolygons: {
        polygon: null,
        geoJson: null,
        initialized: false,
      },
      selectedLocationIndex: null,
      manualLayer: null,
      map: null,
      geoData: null,
      mapInitialized: false,
      geoLayer: null,
    }
  },
  methods: {
    emitLocationSelected(location) {
      let data = {}
      if (!("geojson" in location)) {
        data["geojson"] = location
      } else {
        data = location
      }

      this.$emit("locationSelected", data)
    },

    focusLocation(geo) {
      let tmp = []
      this.geoLayer.clearLayers()
      tmp.push(geo)
      this.setGeoLocation(tmp)
    },
    setMapView(location) {
      this.map.setView(location, 9)
    },
    selectManual() {
      this.selectedLocationIndex = "manual"
      if (!this.manualLayer.getLayers().length) {
        this.drawnPolygons.polygon = new L.geoJSON(this.drawnPolygons.geoJson)
        this.drawnPolygons.polygon.addTo(this.manualLayer)
        this.drawnPolygons.polygon.bringToFront()
      }

      this.emitLocationSelected(this.drawnPolygons.geoJson.geometry)
      this.geoLayer.clearLayers()
      this.setMapView({
        lat: this.drawnPolygons.geoJson.geometry.coordinates[0][0][1],
        lon: this.drawnPolygons.geoJson.geometry.coordinates[0][0][0],
      })
    },
    locationSelected(index, location) {
      this.manualLayer.clearLayers()
      this.selectedLocationIndex = index
      this.setMapView([location.lat, location.lon])
      this.emitLocationSelected(location.geojson)
      this.focusLocation(location)
    },
    removeCoordinate(index) {
      this.polygonCoordinates.splice(index, 1)
      this.polygon.setLatLngs(this.polygonCoordinates)
    },

    redrawConfirmation() {
      return this.$swal({
        title: "Only one polygon is supported",
        text: "Do you want to remove the current polygon and draw another one",
        type: "question",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Redraw",
        cancelButtonText: "Dismiss",
      })
    },

    async manualDrawwing() {
      if (this.polygon === null) {
        this.polygon = L.polyline([]).addTo(this.map)
      }

      if (this.drawnPolygons.initialized) {
        let result = await this.redrawConfirmation()
        if (result.value === true) {
          //convert poly line to polygon+
          this.drawnPolygons.polygon.remove()
          this.manualLayer.clearLayers()
          this.drawnPolygons.polygon = null
          this.drawnPolygons.geoJson = null
          this.drawnPolygons.initialized = false
        } else {
          return
        }
      }

      this.drawCluster = !this.drawCluster
      if (this.drawCluster) {
        //clear layer
        this.geoLayer.clearLayers()
      } else if (this.polygonCoordinates.length > 1) {
        //convert poly line to polygon+
        this.drawnPolygons.polygon = new L.Polygon(this.polygon.getLatLngs())
        this.drawnPolygons.polygon.addTo(this.manualLayer)

        this.drawnPolygons.geoJson = this.drawnPolygons.polygon.toGeoJSON()
        this.drawnPolygons.initialized = true

        this.polygon.remove()
        this.polygon = null
        this.polygonCoordinates = []

        this.selectedLocationIndex = "manual"
        this.emitLocationSelected(this.drawnPolygons.geoJson.geometry)
      }
    },

    getClusterGeoData() {
      if (this.cluster_name.length === 0) {
        this.$swal({
          title: "Name required",
          text: "Please enter the name first",
          type: "warning",
          showCancelButton: false,
        })
        return
      }
      axios
        .get(
          "https://nominatim.openstreetmap.org/search.php?q=" +
            this.cluster_name +
            "&polygon_geojson=1&format=json",
        )
        .then((response) => {
          this.geoData = this.filterResultsOut(response.data)
          if (!this.mapInitialized) {
            this.initMap()
          }

          this.setGeoLocation(response.data)
        })
    },

    filterResultsOut(data) {
      let result = []
      for (let i in data) {
        let geoType = data[i].geojson.type
        if (
          Object.keys(this.filtered_types).length > 0 &&
          !(geoType.toLowerCase() in this.filtered_types)
        ) {
          continue
        }
        result.push(data[i])
      }
      return result
    },

    setGeoLocation(data) {
      let locations = []
      this.geoLayer.clearLayers()
      for (let i in data) {
        let geoType = data[i].geojson.type

        locations.push({
          type: "Feature",
          properties: { popupContent: data[i].display_name },

          geometry: {
            type: geoType,
            coordinates: data[i].geojson.coordinates,
          },
        })

        let geoJsonLayer = L.geoJSON(locations)

        geoJsonLayer.addTo(this.geoLayer)

        this.map.setView([data[i].lat, data[i].lon], 12)
      }
    },

    initMap() {
      this.mapInitialized = true
      //create map
      this.map = L.map("map").setView([-2.500381, 32.88906], 6)
      //set tile
      this.tileLayer = L.tileLayer(
        //'https://cartodb-basemaps-{s}.global.ssl.fastly.net/rastertiles/voyager/{z}/{x}/{y}.png',
        "http://{s}.tile.osm.org/{z}/{x}/{y}.png",
        {
          maxZoom: 18,
          attribution: ' <span style="cursor:pointer">&copy; MpManager</span>',
        },
      )

      this.tileLayer.addTo(this.map)

      this.geoLayer = layerGroup().addTo(this.map)
      this.manualLayer = layerGroup().addTo(this.map)
      var parent = this
      this.map.on("click", function (e) {
        if (parent.drawCluster === true) {
          var coord = e.latlng
          var lat = coord.lat
          var lng = coord.lng

          parent.polygonCoordinates.push([lat, lng])

          if (parent.polygon === null) {
            parent.polygon = L.polyline([[lat, lng]]).addTo(parent.map)
          }
          parent.polygon.setLatLngs(parent.polygonCoordinates)
        }
      })
      this.$emit("mapInitialized", this.map)
    },
  },
}
</script>

<style scoped></style>
