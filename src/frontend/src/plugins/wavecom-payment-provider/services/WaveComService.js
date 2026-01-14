import { ErrorHandler } from "@/Helpers/ErrorHandler"
import WaveComRepository from "../repositories/WaveComRepository"

export class WaveComService {
  constructor() {
    this.reasons = []
  }

  async upload(filePath) {
    const formData = new FormData()

    if (filePath === null) {
      return
    }

    try {
      formData.append("transaction_file", filePath)
      const { data, status } = await WaveComRepository.post(formData, {
        header: { "Content-Type": "csv" },
      })
      if (status !== 200 && status !== 201) {
        return new ErrorHandler("Failed with http status ", status)
      }

      if ("reason" in data.data) {
        this.reasons = data.data["reason"]
      }
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
}
