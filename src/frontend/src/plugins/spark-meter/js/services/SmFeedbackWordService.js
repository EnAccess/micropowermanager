import { ErrorHandler } from "@/Helpers/ErrorHandler"
import SmFeedbackWordRepository from "../repositories/SmFeedbackWordRepository"

export class SmFeedbackWordService {
  constructor() {
    this.repository = SmFeedbackWordRepository
    this.feedbackWords = {
      id: null,
      meterReset: null,
      meterBalance: null,
    }
  }

  fromJson(feedbackWordsData) {
    this.feedbackWords = {
      id: feedbackWordsData.id,
      meterReset: feedbackWordsData.meter_reset,
      meterBalance: feedbackWordsData.meter_balance,
    }
    return this.feedbackWords
  }

  async getFeedbackWords() {
    try {
      let response = await this.repository.list()
      if (response.status !== 200) {
        return new ErrorHandler(response.error, "http", response.status)
      }
      return this.fromJson(response.data.data[0])
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async updateFeedbackWords() {
    try {
      let updateWordsPM = {
        id: this.feedbackWords.id,
        meter_reset: this.feedbackWords.meterReset,
        meter_balance: this.feedbackWords.meterBalance,
      }

      let response = await this.repository.put(updateWordsPM)
      if (response.status !== 200 && response.status !== 201) {
        return new ErrorHandler(response.error, "http", response.status)
      }
      return this.fromJson(response.data.data)
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
