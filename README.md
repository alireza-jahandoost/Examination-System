<div id="top"></div>

<!-- PROJECT SHIELDS -->
<!--
*** I'm using markdown "reference style" links for readability.
*** Reference links are enclosed in brackets [ ] instead of parentheses ( ).
*** See the bottom of this document for the declaration of the reference variables
*** for contributors-url, forks-url, etc. This is an optional, concise syntax you may use.
*** https://www.markdownguide.org/basic-syntax/#reference-style-links
-->
[![Contributors][contributors-shield]][contributors-url]
[![Forks][forks-shield]][forks-url]
[![Stargazers][stars-shield]][stars-url]
[![Issues][issues-shield]][issues-url]
[![MIT License][license-shield]][license-url]
[![LinkedIn][linkedin-shield]][linkedin-url]



<!-- PROJECT LOGO -->
<br />
<div align="center">
  <a href="https://github.com/othneildrew/Best-README-Template">
    <img src="https://github.com/alireza-jahandoost/examination-system-frontend/blob/main/public/favicon.ico" alt="Logo" width="80" height="80">
  </a>

  <h3 align="center">Exams Galaxy</h3>

  <p align="center">
      The back end part of <a href="https://examsgalaxy.com">Exams Galaxy</a> website
    <br />
    <a href="https://github.com/alireza-jahandoost/Examination-System"><strong>Explore the docs »</strong></a>
    <br />
    <br />
    <a href="https://examsgalaxy.com/">View Website</a>
    ·
    <a href="https://github.com/alireza-jahandoost/Portfolio/issues">Report Bug</a>
    ·
    <a href="https://github.com/alireza-jahandoost/Portfolio/issues">Request Feature</a>
  </p>
</div>



<!-- TABLE OF CONTENTS -->
<details>
  <summary>Table of Contents</summary>
  <ol>
    <li>
      <a href="#about-the-project">About The Project</a>
      <ul>
        <li><a href="#built-with">Built With</a></li>
      </ul>
    </li>
    <li>
      <a href="#getting-started">Getting Started</a>
      <ul>
        <li><a href="#prerequisites">Prerequisites</a></li>
        <li><a href="#installation">Installation</a></li>
      </ul>
    </li>
    <li><a href="#usage">Usage</a></li>
    <li><a href="#roadmap">Roadmap</a></li>
    <li><a href="#contributing">Contributing</a></li>
    <li><a href="#license">License</a></li>
    <li><a href="#contact">Contact</a></li>
    <li><a href="#acknowledgments">Acknowledgments</a></li>
  </ol>
</details>



<!-- ABOUT THE PROJECT -->
## About The Project

<div align="center">
  <a href="https://alirezajahandoost.com">
    <img src="images/screenshot.png" alt="Screenshot of project">
  </a>
</div>

Exams galaxy is an examining platform, with supporting of 6 types of questions, auto correction of 5 types of questions, unlimited number of participants and questions, ...

Features:
* support 6 types of questions (multiple answer, descriptive, true/false, ordering, fill the blank and select the answer)
* auto correction
* setting password for exam( for private exams )
* unlimited number of questions
* unlimited participants

<p align="right">(<a href="#top">back to top</a>)</p>



### Built With

* [Laravel](https://laravel.com)

<p align="right">(<a href="#top">back to top</a>)</p>



<!-- GETTING STARTED -->
## Getting Started

### Prerequisites

To run this project in your pc, you need:
* php
* a database server such as mysql
* a web server such as apache

### Installation

1. Clone the repo
   ```sh
   git clone https://github.com/alireza-jahandoost/Examination-System
   ```
2. Move to the directory
   ```sh
   cd Examination-System
   ```
3. Configure `.env` file( you only need to to write database information )
   ```sh
   cp .env.example .env
   ```
4. Install php dependencies
   ```sh
   composer install
   ```
5. Generate key
   ```sh
   php artisan key:generate
   ```
6. Create database tables
   ```sh
   php artisan migrate
   ```

<p align="right">(<a href="#top">back to top</a>)</p>



<!-- USAGE EXAMPLES -->
## Usage

To run the project, you need to serve it:
   ```sh
   php artisan serve
   ```
Then, the project is available on `http://localhost:8000/login`.

<p align="right">(<a href="#top">back to top</a>)</p>



<!-- ROADMAP -->
<!-- ## Roadmap

- [x] customizable `About me` and `Contact me` sections
- [x] unlimited number of projects to include
- [x] categorizing projects in project sections
- [x] add unlimited number of skills
- [x] categorizing skills into `Fluented` and `Familiar` categories
- [x] filtering projects by skills or project sections
- [x] search in projects
- [ ] supporting of having a blog
- [ ] showing the pdf version of pdf in landing page
- [ ] add a form in `Contact me` section to communicate easier
- [ ] supporting rtl
- [ ] supporting multi languages

See the [open issues](https://github.com/alireza-jahandoost/Portfolio/issues) for a full list of proposed features (and known issues).

<p align="right">(<a href="#top">back to top</a>)</p> -->



<!-- CONTRIBUTING -->
## Contributing

Contributions are what make the open source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

If you have a suggestion that would make this better, please fork the repo and create a pull request. You can also simply open an issue with the tag "enhancement".
If you do not have a specific idea, and you want to contribute, you can choose one of the issues and try to complete that one.
Don't forget to give the project a star! Thanks again!

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

<p align="right">(<a href="#top">back to top</a>)</p>



<!-- LICENSE -->
## License

Distributed under the MIT License. See `LICENSE` for more information.

<p align="right">(<a href="#top">back to top</a>)</p>



<!-- CONTACT -->
## Contact

Alireza Jahandoost - alireza.jhd2000@gmail.com

Project Link: [https://github.com/alireza-jahandoost/Examination-System](https://github.com/alireza-jahandoost/Examination-System)

<p align="right">(<a href="#top">back to top</a>)</p>

<!-- MARKDOWN LINKS & IMAGES -->
<!-- https://www.markdownguide.org/basic-syntax/#reference-style-links -->
[contributors-shield]: https://img.shields.io/github/contributors/alireza-jahandoost/Examination-System.svg?style=for-the-badge
[contributors-url]: https://github.com/alireza-jahandoost/Examination-System/graphs/contributors
[forks-shield]: https://img.shields.io/github/forks/alireza-jahandoost/Examination-System.svg?style=for-the-badge
[forks-url]: https://github.com/alireza-jahandoost/Examination-System/network/members
[stars-shield]: https://img.shields.io/github/stars/alireza-jahandoost/Examination-System?style=for-the-badge
[stars-url]: https://github.com/alireza-jahandoost/Examination-System/stargazers
[issues-shield]: https://img.shields.io/github/issues/alireza-jahandoost/Examination-System.svg?style=for-the-badge
[issues-url]: https://github.com/alireza-jahandoost/Examination-System/issues
[license-shield]: https://img.shields.io/github/license/alireza-jahandoost/Examination-System.svg?style=for-the-badge
[license-url]: https://github.com/alireza-jahandoost/Examination-System/blob/master/LICENSE
[linkedin-shield]: https://img.shields.io/badge/-LinkedIn-black.svg?style=for-the-badge&logo=linkedin&colorB=555
[linkedin-url]: https://www.linkedin.com/in/alireza-jahandoost
[product-screenshot]: images/screenshot.png
