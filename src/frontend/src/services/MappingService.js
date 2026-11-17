import eBikeIcon from "@/assets/icons/ebike.png"
import meterIcon from "@/assets/icons/meter.png"
import miniGridIcon from "@/assets/icons/miniGrid.png"
import miniGridIcon2 from "@/assets/icons/miniGrid2.png"
import shsIcon from "@/assets/icons/shs.png"
import villageMarkerIcon from "@/assets/icons/village.png"
import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import MappingRepository from "@/repositories/MappingRepository.js"

// Country bounding boxes, looked up at most once per page load: Nominatim asks
// clients to cache results and keep their request volume low.
const countryAreas = new Map()

export const MARKER_TYPE = {
  METER: "METER",
  SHS: "SHS",
  MINI_GRID: "MINI_GRID",
  MINI_GRID_2: "MINI_GRID_2",
  VILLAGE: "VILLAGE",
  E_BIKE: "E_BIKE",
}
export const ICONS = {
  METER: meterIcon,
  SHS: shsIcon,
  MINI_GRID: miniGridIcon,
  MINI_GRID_2: miniGridIcon2,
  VILLAGE: villageMarkerIcon,
  E_BIKE: eBikeIcon,
}

export const DEVICE_TYPE_LABELS = {
  [MARKER_TYPE.METER]: "Meter",
  [MARKER_TYPE.SHS]: "SHS",
  [MARKER_TYPE.E_BIKE]: "e-Bike",
}

// Hierarchical marker tooltip: Cluster > Site > Device ID > Device Type.
// Only the rows whose value is present are rendered, in that fixed order, so a
// site marker shows Cluster + Site while a device marker adds Device ID + Type.
export const markerTooltip = (markingInfo) =>
  [
    ["Cluster", markingInfo.clusterName],
    ["Site", markingInfo.siteName],
    ["Device ID", markingInfo.serialNumber],
    ["Device Type", DEVICE_TYPE_LABELS[markingInfo.markerType]],
  ]
    .filter(([, value]) => value)
    .map(([label, value]) => `<strong>${label}:</strong> ${value}`)
    .join("<br>")

export class MappingService {
  constructor() {
    this.repository = MappingRepository
    this.constantMarkerUrl = null
    this.markerUrl = null
    this.markingInfos = []
    this.locations = []
    this.geoDataItems = []
    this.editableLayers = null
    this.markingInfo = {
      id: 0,
      name: "",
      lat: 0,
      lon: 0,
      markerType: null,
      deviceType: null,
      iconUrl: null,
    }
    this.geoData = []
    this.searchedOrDrawnItems = []
  }

  async getSearchResult(name, filteredTypes) {
    try {
      const { data, error, status } = await this.repository.get(name)
      if (status !== 200) return new ErrorHandler(error, "http", status)

      const normalizedFeatures = (data.features || [])
        .map((f) => this.normalizeFeatureGeometry(f))
        .filter(Boolean)

      const filteredFeatures = this.filterGeoJSONFeatures(
        normalizedFeatures,
        filteredTypes,
      )

      // Store for list display
      this.searchedOrDrawnItems = filteredFeatures.map((feature) => ({
        feature: feature,
        display_name:
          feature.properties?.display_name || feature.properties?.name || "",
        searched: true,
      }))

      return this.searchedOrDrawnItems
    } catch (e) {
      const errorMessage = e.response.data.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  filterGeoJSONFeatures(features, filteredTypes) {
    if (Object.keys(filteredTypes).length === 0) {
      return features
    }

    return features.filter((feature) => {
      const geoType = feature.geometry?.type?.toLowerCase()
      return geoType && geoType in filteredTypes
    })
  }

  /**
   * Converts non-polygon geometries (Point, LineString) into a Polygon
   * derived from the feature's bounding box. This ensures downstream code
   * that expects a polygon geometry always receives one.
   */
  normalizeFeatureGeometry(feature) {
    const polygonTypes = ["Polygon", "MultiPolygon"]
    if (polygonTypes.includes(feature.geometry?.type)) {
      return feature
    }

    // Fall back to bbox → bounding rectangle polygon
    const bbox = feature.bbox
    if (!bbox || bbox.length < 4) {
      console.warn("Feature has no usable geometry or bbox, skipping:", feature)
      return null
    }

    const [minLon, minLat, maxLon, maxLat] = bbox
    return {
      ...feature,
      geometry: {
        type: "Polygon",
        coordinates: [
          [
            [minLon, minLat],
            [maxLon, minLat],
            [maxLon, maxLat],
            [minLon, maxLat],
            [minLon, minLat],
          ],
        ],
      },
    }
  }

  strToHex(str) {
    str += "z4795dfjkldfnjk4lnjkl"
    let hash = 0
    for (let i = 0; i < str.length; i++) {
      hash = str.charCodeAt(i) + ((hash << 5) - hash)
    }
    let colour = "#"
    for (let i = 0; i < 3; i++) {
      let value = (hash >> (i * 8)) & 0xff
      colour += ("00" + value.toString(16)).substr(-2)
    }
    return colour
  }

  focusLocation(geo) {
    let tmp = []
    tmp.push(geo)
    return tmp
  }

  manualDrawingLocationConvert(geoDataItem) {
    const locations = []
    for (const coordinates of geoDataItem.geojson.coordinates) {
      for (const coordinate of coordinates) {
        const { lat, lng } = coordinate
        if (lat === undefined && lng === undefined) {
          locations.push(coordinate)
        } else {
          locations.push([lat, lng])
        }
      }
    }
    geoDataItem.geojson.coordinates[0] = [...locations]

    return geoDataItem
  }

  createMarkingInformation(
    id,
    name,
    serialNumber,
    lat,
    lon,
    deviceType = null,
    iconUrl = null,
  ) {
    this.markingInfo = {
      id: id,
      serialNumber: serialNumber,
      name: name,
      lat: lat,
      lon: lon,
      deviceType: deviceType,
      iconUrl: iconUrl,
    }
    return this.markingInfo
  }

  setMarkingInfos(markingInfos) {
    this.markingInfos = markingInfos
  }

  setGeoData(geoData) {
    // geoData must be a GeoJSON Feature, FeatureCollection, or array of Features
    if (Array.isArray(geoData)) {
      this.geoData = geoData
    } else if (geoData.type === "FeatureCollection") {
      this.geoData = geoData.features || []
    } else if (geoData.type === "Feature") {
      this.geoData = [geoData]
    } else {
      throw new Error(
        "geoData must be a GeoJSON Feature, FeatureCollection, or array of Features",
      )
    }
  }

  /**
   * Load a cluster ({ geo_json, name }) as the map's geo data: normalizes its
   * geo_json (Feature or FeatureCollection) into a single Feature carrying the
   * cluster name, and returns the Feature.
   * Returns null when the cluster has no geo data.
   */
  setClusterGeoData(cluster) {
    if (!cluster || !cluster.geo_json) {
      return null
    }

    let feature
    if (cluster.geo_json.type === "Feature") {
      feature = cluster.geo_json
    } else if (cluster.geo_json.type === "FeatureCollection") {
      feature = cluster.geo_json.features[0]
    } else {
      throw new Error(
        "cluster.geo_json must be a GeoJSON Feature or FeatureCollection",
      )
    }

    feature = {
      ...feature,
      properties: {
        ...feature.properties,
        name: cluster.name || "",
      },
    }

    this.setGeoData(feature)
    return feature
  }

  setConstantMarkerUrl(constantMarkerUrl) {
    this.constantMarkerUrl = constantMarkerUrl
  }

  setMarkerUrl(markerUrl) {
    this.markerUrl = markerUrl
  }

  getGeoData() {
    return this.geoData
  }

  // Bounding box ([[west, south], [east, north]]) of `country`, or null when it
  // is not set or cannot be resolved.
  async getCountryArea(country) {
    if (!country) return null
    if (!countryAreas.has(country)) {
      countryAreas.set(
        country,
        this.repository
          .getCountry(country)
          .then(({ data }) => {
            const box = data[0]?.boundingbox
            if (!box) return null
            const [south, north, west, east] = box.map(Number)
            return [
              [west, south],
              [east, north],
            ]
          })
          .catch(() => null),
      )
    }
    return countryAreas.get(country)
  }
}
