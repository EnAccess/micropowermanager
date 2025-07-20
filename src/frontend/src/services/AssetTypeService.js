import { EventBus } from "@/shared/eventbus"
import { ErrorHandler } from "@/Helpers/ErrorHandler"

import AssetTypeRepository from "@/repositories/AssetTypeRepository"

export class AssetTypeService {
  constructor() {
    this.repository = AssetTypeRepository
    this.list = []
    this.assetType = {
      id: null,
      name: null,
      updated_at: null,
      edit: false,
    }
  }

  fromJson(data) {
    return {
      id: data.id,
      name: data.name,
      updatedAt: data.updated_at
        .toString()
        .replace(/T/, " ")
        .replace(/\..+/, ""),
    }
  }

  updateList(data) {
    this.list = data.map((asset) => {
      return {
        id: asset.id,
        name: asset.name,
        updated_at: asset.updated_at
          ? asset.updated_at.toString().replace(/T/, " ").replace(/\..+/, "")
          : "",
        edit: false,
      }
    })
    return this.list
  }

  async createAssetType() {
    try {
      let response = await this.repository.create(this.assetType)
      if (response.status === 200 || response.status === 201) {
        this.assetType.id = response.data.data.id
        this.assetType.name = response.data.data.name
        this.assetType.updated_at = response.data.data.updated_at
        EventBus.$emit("assetTypeAdded", this.assetType)
        this.resetAssetType()
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async updateAssetType(assetType) {
    try {
      const response = await this.repository.update(assetType)
      if (response.status === 200 || response.status === 201) {
        return response
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async deleteAssetType(assetType) {
    try {
      let response = await this.repository.delete(assetType.id)
      if (response.status === 200 || response.status === 201) {
        return response
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getAssetsTypes() {
    try {
      this.list = []
      let response = await this.repository.list()
      if (response.status === 200 || response.status === 201) {
        for (const assetType of response.data.data) {
          this.list.push(this.fromJson(assetType))
        }
      } else {
        new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  resetAssetType() {
    this.assetType = {
      id: null,
      name: null,
      updated_at: null,
      edit: false,
      asset_type_name: null,
      price: null,
    }
  }
}
