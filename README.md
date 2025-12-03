# Moodle APIDocs Course Module Plugin

**Native rendering of technical documentation (AsyncAPI, OpenAPI, Markdown) within Moodle.**

## 📖 The Story & Motivation

This plugin was born out of a specific need in **Computer Science Campuses and Technical Training Environments**.

In typical LMS environments, sharing API specifications usually means uploading a raw YAML file for students to download, or embedding an external URL (SwaggerHub, etc.) via iframe. Neither solution provides a seamless learning experience.

We wanted to bring the developer experience (DX) directly into the course. Students should be able to read, explore, and implement APIs **without leaving the Moodle interface** and without depending on external tools that might be blocked by campus firewalls.

### 💡 The Inspiration

Our main inspiration was the **GitLab default OpenAPI viewer** and professional IDEs like **WebStorm**.

We loved how GitLab detects a spec file and simply *renders* it ("zero-friction"). We also loved how WebStorm allows developers to switch between different visualization engines. We brought both concepts into Moodle: you upload the spec, Moodle renders the documentation. Simple.


## 🚀 Key Features

* **Offline First / Privacy Focused:** Unlike other plugins that rely on CDNs (unpkg/jsdelivr), this plugin injects the rendering engines (React, AsyncAPI Standalone, Swagger UI, Redoc) directly from the server's local storage. It works perfectly in **Intranets** or air-gapped environments.
* **OpenAPI Dual Engine:** Why choose between? We implemented a **Smart Switcher** that allows users to toggle instantly between **Swagger UI** and **Redoc**, similar to professional IDEs.
* **AsyncAPI v3 Support:** Native support for the latest Event-Driven Architecture specifications (Kafka, MQTT, WebSockets) using the official React Standalone component.
* **GitLab-Style Toolbar:** Switch instantly between the **Rendered View** (Visual) and **Raw Code** (YAML/JSON) with a single click. Also includes Dark Mode and Copy-to-Clipboard functionality.
* **Clean UI:** Removes Moodle's standard clutter (navigation blocks, footers, completion buttons) to provide a "Full Focus" reading mode.

## 📄 Supported Formats

1.  **AsyncAPI (v2.0 - v3.0):**
    * Renders utilizing the *AsyncAPI React Component*.
    * Supports complex schemas and event-driven definitions.
### **Example**
   ![AsyncAPI Example](image-examples/async.example.png)

2.  **OpenAPI (Swagger v3):**
    * **Dual Engine Support:**
        * **Swagger UI:** Interactive "Try it out" functionality.
        * **Redoc:** Three-column layout, perfect for deep reading and documentation study.
    * Users can switch views instantly via the toolbar.
### **Example:**
   ![OpenAPI Example](image-examples/open.example.png)

3.  **Markdown (.md):**
    * Renders GitHub-Flavored Markdown.
    * Perfect for Readmes, changelogs, and general technical guides.

## 🛠 Installation

1.  Clone this repository into your Moodle `mod/` directory:
    ```bash
    git clone [https://github.com/x-name15/moodle-apidocs-course-module.git](https://github.com/x-name15/moodle-apidocs-course-module.git) mod/apidocs
    ```
2.  Log in to Moodle as Administrator.
3.  Run the database upgrade/installation process.

## 🤝 Contributing

Pull requests are welcome, always :D

## 📝 License

Licensed under the [GNU General Public License v3](http://www.gnu.org/copyleft/gpl.html).