import WhatsappTemplateRepository, {
  resource,
} from "../repositories/WhatsappTemplateRepository.js"

import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import { Paginator } from "@/Helpers/Paginator.js"

export class WhatsappTemplateService {
  constructor() {
    this.repository = WhatsappTemplateRepository
    this.notifications = []
    this.manualTemplates = []
  }

  templatesPaginator() {
    return new Paginator(resource)
  }

  async getNotifications() {
    try {
      this.notifications = (await this.repository.notifications()).data.data
    } catch (e) {
      return new ErrorHandler(e.response?.data?.message ?? e.message, "http")
    }
  }

  // The backend decides which templates operators may send; the send screens only list them.
  async getManualTemplates() {
    try {
      this.manualTemplates = (await this.repository.manual()).data.data
    } catch (e) {
      return new ErrorHandler(e.response?.data?.message ?? e.message, "http")
    }
  }

  async refreshTemplates() {
    try {
      await this.repository.refresh()
    } catch (e) {
      return new ErrorHandler(e.response?.data?.message ?? e.message, "http")
    }
  }
}
