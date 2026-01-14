import { ErrorHandler } from "@/Helpers/ErrorHandler"
import CsvUploadRepository from "../repositories/CsvUploadRepository"

export class CsvUploadService {
  constructor() {
    this.repository = CsvUploadRepository
    this.recentlyCreatedRecords = {
      cluster: 0,
      miniGrid: 0,
      village: 0,
      customer: 0,
      tariff: 0,
      connectionType: 0,
      connectionGroup: 0,
      meter: 0,
    }
  }

  async create(csvData) {
    let formData = new FormData()
    if (csvData == null) {
      return
    }
    formData.append("csv", csvData)
    try {
      const { data, status } = await this.repository.post(formData, {
        header: { "Content-Type": "csv" },
      })

      if (data.data.attributes.alert !== "") {
        return new ErrorHandler(data.data.attributes.alert, "http", 422)
      }
      if (!status === 201) {
        return new ErrorHandler("Failed", status)
      }
      this.fillRecentlyCreatedRecords(
        data.data.attributes.recently_created_records,
      )

      return data.data
    } catch (error) {
      if (error.response) {
        if (error.response.status && error.response.status === 422) {
          const errorMessage = error.response.data.message.csv[0]

          return new ErrorHandler(errorMessage, "http", 422)
        }
        const errorMessage = error.response.data.message

        return new ErrorHandler(errorMessage, "http", 400)
      } else {
        const errorMessage = error.message

        return new ErrorHandler(errorMessage, "http")
      }
    }
  }

  fillRecentlyCreatedRecords(recentlyCreatedRecords) {
    this.recentlyCreatedRecords = {
      cluster: recentlyCreatedRecords.cluster,
      miniGrid: recentlyCreatedRecords.mini_grid,
      village: recentlyCreatedRecords.village,
      customer: recentlyCreatedRecords.customer,
      tariff: recentlyCreatedRecords.tariff,
      connectionType: recentlyCreatedRecords.connection_type,
      connectionGroup: recentlyCreatedRecords.connection_group,
      meter: recentlyCreatedRecords.meter,
    }
  }
}
