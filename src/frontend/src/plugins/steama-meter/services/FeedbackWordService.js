import { ErrorHandler } from "@/Helpers/ErrorHandler"
import FeedbackWordRepository from "../repositories/FeedbackWordRepository"

export class FeedbackWordService {
  constructor() {
    this.repository = FeedbackWordRepository
    this.feedbackWords = {
      id: null,
      meterReset: null,
      meterBalance: null,
    }
  }

  fromJson(feedbackWordsData) {
    this.feedbackWords = {
      id: feedbackWordsData.id,
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
