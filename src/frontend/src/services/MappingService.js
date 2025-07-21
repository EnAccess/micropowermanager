import { ErrorHandler } from "@/Helpers/ErrorHandler"
import store from "@/store/store"
import meterIcon from "@/assets/icons/meter.png"
import shsIcon from "@/assets/icons/shs.png"
import miniGridIcon2 from "@/assets/icons/miniGrid2.png"
import miniGridIcon from "@/assets/icons/miniGrid.png"
import villageMarkerIcon from "@/assets/icons/village.png"
import eBikeIcon from "@/assets/icons/ebike.png"

import MappingRepository from "@/repositories/MappingRepository"

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
export const ICON_OPTIONS = {
  iconSize: [40.4, 44],
  iconAnchor: [20, 43],
  popupAnchor: [0, -51],
}

export class MappingService {
  constructor() {
    this.repository = MappingRepository
    this.center = [
      store.getters["settings/getMapSettings"].latitude,
      store.getters["settings/getMapSettings"].longitude,
    ]
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
      this.searchedOrDrawnItems = this.filterResultsOut(data, filteredTypes)
      return this.searchedOrDrawnItems
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  filterResultsOut(geoData, filteredTypes) {
    this.searchedOrDrawnItems = []
    return geoData.filter((data) => {
      const geoType = data.geojson.type.toLowerCase()

      if (
        Object.keys(filteredTypes).length > 0 &&
        !(geoType in filteredTypes)
      ) {
        return false
      }
      data.searched = true
      return true
    })
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

  setCenter(center) {
    this.center = center
  }

  setGeoData(geoData) {
    this.geoData = geoData
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

  getCenter() {
    return this.center
  }
}
