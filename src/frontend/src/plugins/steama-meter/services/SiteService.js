import SiteRepository from "../repositories/SiteRepository.js"

import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import { Paginator } from "@/Helpers/Paginator.js"
import { geoJsonToLatLon } from "@/Helpers/Utils.js"

export class SiteService {
  constructor() {
    this.repository = SiteRepository
    this.list = []
    this.isSync = false
    this.count = 0
    this.pagingUrl = "/api/steama-meters/steama-site"
    this.routeName = "/steama-meters/steama-site"
    this.paginator = new Paginator(this.pagingUrl)
    this.site = {
      id: null,
      name: null,
      latitude: null,
      longitude: null,
    }
  }

  fromJson(siteData) {
    const location = geoJsonToLatLon(siteData.mpm_mini_grid?.location)
    this.site = {
      id: siteData.id,
      name: siteData.mpm_mini_grid?.name ?? null,
      latitude: location?.lat ?? null,
      longitude: location?.lon ?? null,
    }
    return this.site
  }

  updateList(data) {
    this.list = []
    for (let s in data) {
      let site = this.fromJson(data[s])
      this.list.push(site)
    }
  }

  async syncSites() {
    try {
      let response = await this.repository.sync()
      if (response.status === 200) {
        return this.updateList(response.data.data)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response?.data?.message ?? e.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getSitesCount() {
    try {
      let response = await this.repository.count()
      if (response.status === 200) {
        this.count = response.data
        return this.count
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response?.data?.message ?? e.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async checkLocation() {
    try {
      let response = await this.repository.location()
      if (response.status === 200) {
        return response.data.data
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response?.data?.message ?? e.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
