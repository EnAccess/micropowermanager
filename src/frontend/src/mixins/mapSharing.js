import store from "@/store/store"
import { ICON_OPTIONS, MappingService } from "@/services/MappingService"
import "leaflet/dist/leaflet.css"
import "leaflet-draw/dist/leaflet.draw.css"
import "leaflet.markercluster/dist/MarkerCluster.css"
import "leaflet.markercluster/dist/MarkerCluster.Default.css"
import L from "leaflet"
import "leaflet.markercluster"
import "leaflet.featuregroup.subgroup"
import "leaflet-draw"
import "leaflet-bing-layer"
import marker from "leaflet/dist/images/marker-icon.png"
import { EventBus } from "@/shared/eventbus"

export const sharedMap = {
  props: {
    mappingService: {
      type: MappingService,
      required: true,
    },
    zoom: {
      type: Number,
      default: store.getters["settings/getMapSettings"].zoom,
    },
    maxZoom: {
      type: Number,
      default: 20,
    },

    mutatingCenter: {
      type: Array,
      required: false,
    },
    position: {
      type: String,
      default: "topright",
      required: false,
    },
    polygon: {
      type: Boolean,
      default: false,
    },
    polyline: {
      type: Boolean,
      default: false,
    },
    marker: {
      type: Boolean,
      default: false,
    },
    markerCount: {
      type: Number,
      default: 1,
    },
    circlemarker: {
      type: Boolean,
      default: false,
    },
    rectangle: {
      type: Boolean,
      default: false,
    },
    circle: {
      type: Boolean,
      default: false,
    },
    remove: {
      type: Boolean,
      default: false,
    },
    edit: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      markerUrl: this.mappingService.markerUrl,
      defaultMarkerIconUrl: marker,
      osmUrl: "http://{s}.tile.osm.org/{z}/{x}/{y}.png",
      osmAttrib: '<span style="cursor:pointer">&copy; MpManager</span>',
      osm: null,
      map: null,
      editableLayer: null,
      nonEditableLayer: null,
      markersLayer: null,
      drawingOptions: {},
      geoDataItems: [],
    }
  },
  mounted() {
    this.initMap()
  },
  destroyed() {
    this.map = null
  },
  methods: {
    initMap() {
      const drawingOptions = {
        position: this.position,
        draw: {
          polygon: this.polygon,
          polyline: this.polyline,
          marker: this.marker,
          circlemarker: this.circlemarker,
          rectangle: this.rectangle,
          circle: this.circle,
        },
        edit: {
          featureGroup: new L.FeatureGroup(),
          remove: this.remove,
          edit: this.edit,
        },
      }
      this.map = L.map("map").setView(this.mappingService.center, this.zoom)
      this.setTileLayer()
      if (drawingOptions.draw.marker) {
        const marker = L.Icon.extend({
          options: { ...ICON_OPTIONS, iconUrl: this.markerUrl },
        })
        drawingOptions.draw.marker = {}
        drawingOptions.draw.marker.icon = new marker()
      }
      this.markersLayer = new L.markerClusterGroup({
        chunkedLoading: true,
        spiderfyOnMaxZoom: true,
      })
      const drawControl = new L.Control.Draw(drawingOptions)
      this.map.addLayer(drawingOptions.edit.featureGroup)
      this.map.addControl(drawControl)
      this.editableLayer = drawingOptions.edit.featureGroup
      this.nonEditableLayer = new L.FeatureGroup()
    },
    setTileLayer() {
      L.tileLayer(this.osmUrl, {
        maxZoom: this.maxZoom,
        attribution: this.osmAttrib,
      }).addTo(this.map)
    },
    reGenerateMap(mutatingCenter) {
      this.map.flyTo(mutatingCenter, this.zoom, this.drawingOptions)
    },
    routeToDetail(path, id) {
      this.$router.push(`${path}/${id}`)
    },
    routeToDetailWithQueryParam(path, key, value) {
      this.$router.push(`${path}?${key}=${value}`)
    },
    focusOnItem(newLat, newLng) {
      this.map.setView([newLat, newLng], this.zoom)
    },
    getLatLng() {
      let zoom
      this.map.on("move", function (e) {
        zoom = Math.round(e.target._zoom)
        EventBus.$emit("mapZoom", zoom)
      })
      return {
        lat: this.map.getCenter().lat.toFixed(5),
        lng: this.map.getCenter().lng.toFixed(5),
        zoom: zoom,
      }
    },
    removeExistingMarkers() {
      this.map.eachLayer(function (layer) {
        if (layer._icon) {
          layer.remove()
        }
      })
    },
  },
  computed: {
    mapProvider() {
      return store.getters["settings/getMapSettings"].provider === "Bing Maps"
    },
  },
  watch: {
    mutatingCenter() {
      this.reGenerateMap(this.mutatingCenter)
    },
  },
}
