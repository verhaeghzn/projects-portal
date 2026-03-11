# ME Projects Portal

A small but modern web application for managing and showcasing research projects at the Department of Mechanical Engineering of Eindhoven University of Technology. The department is organized into three divisions—Thermo-Fluids Engineering (TFE), Computational and Experimental Mechanics (CEM), and Dynamical Systems Design (DSD). The portal enables students to discover available bachelor thesis projects and master thesis projects across the department, while providing administrators and supervisors with powerful tools to manage projects and track their status.

## 🎯 Features

### Public Features
- **Project Browsing**: Browse available research projects with filtering by:
  - Project type (Bachelor Thesis, Master Thesis)
  - Research nature (Experimental, Numerical, etc.)
  - Research section/group
  - Focus areas (Metals, Steel, 3D printing, Meta materials, etc.)
  - Supervisor
  - Company involvement
- **Project Details**: View detailed information about each project, including descriptions, supervisors, and contact information
- **Past Projects**: Archive of completed projects
- **Contact**: Information about the Department of Mechanical Engineering and project inquiries

### Admin Features (Filament Panel)
- **Project Management**: Create, edit, and manage projects with rich content editing
- **User Management**: Manage administrators and supervisors with role-based permissions
- **Tag Management**: Organize projects with categorized tags (Group, Nature, Focus)
- **Organization Management**: Track external organizations/companies associated with projects
- **Section & Group Management**: Organize the division structure
- **Project Status Tracking**: Track which projects are available or taken (assigned to students)

## 🛠️ Tech Stack

- **Framework**: [Laravel](https://laravel.com) 12
- **Admin Panel**: [Filament](https://filamentphp.com) 4
- **Frontend**: [Tailwind CSS](https://tailwindcss.com) 4
- **Build Tool**: [Vite](https://vitejs.dev)
- **PHP Version**: 8.2+

## 📋 Requirements

- PHP 8.2 or higher
- Composer
- Node.js and npm
- MySQL/PostgreSQL/SQLite database
- Web server (Apache/Nginx) or PHP built-in server

## 🚀 Installation

### Quick Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd projects-portal
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database**
   
   Edit `.env` file with your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=projects_portal
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Run migrations and seeders**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Build assets**
   ```bash
   npm run build
   ```

7. **Create storage link**
   ```bash
   php artisan storage:link
   ```

## 🏃 Development

### Start Development Server

The project includes a convenient development script that runs all necessary services:

```bash
composer run dev
```

This starts:
- Laravel development server
- Queue worker
- Log viewer (Pail)
- Vite dev server (hot reload)

### Manual Setup

If you prefer to run services individually:

```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Queue worker (if using queues)
php artisan queue:work

# Terminal 3: Asset compilation
npm run dev
```

### Database Development

```bash
# Run migrations
php artisan migrate

# Run seeders (if available)
php artisan db:seed

# Refresh database
php artisan migrate:fresh --seed
```

## 🧪 Testing

Run the test suite using Pest:

```bash
composer run test
```

Or directly:

```bash
php artisan test
```

## 📁 Project Structure

```
projects-portal/
├── app/
│   ├── Filament/          # Filament admin panel resources
│   │   ├── Resources/     # CRUD resources (Projects, Users, Tags, etc.)
│   │   └── Widgets/       # Dashboard widgets
│   ├── Http/
│   │   └── Controllers/   # Web controllers
│   ├── Livewire/          # Livewire components
│   ├── Models/            # Eloquent models
│   └── Policies/          # Authorization policies
├── database/
│   ├── migrations/        # Database migrations
│   └── seeders/           # Database seeders
├── public/                # Public assets and entry point
├── resources/
│   ├── views/             # Blade templates
│   ├── css/               # Stylesheets
│   └── js/                # JavaScript files
└── routes/
    └── web.php            # Web routes
```

## 🔐 Roles & Permissions

The application uses [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission) for role-based access control.

### Available Roles

- **Administrator**: Full access to all features and resources
- **Staff member - supervisor**: Can create and update projects; manage organizations
- **Researcher**: Can create and update projects; manage organizations

### Project Ownership

- Projects can have a **project owner** (who created the project)
- Projects can have multiple **supervisors** (with order ranking)
- Supervisors and Researchers can manage projects where they are the owner; Supervisors can also manage projects they supervise

## 📝 Project Types

Projects can be one of the following types:

- **Bachelor Thesis**: Bachelor's degree thesis projects
- **Master Thesis**: Master's degree thesis projects

## 🏷️ Tag System

Projects can be tagged with multiple tags from three categories:

- **Group**: Research groups/sections (e.g., associated with specific professors)
- **Nature**: Research methodology (Experimental, Numerical, etc.)
- **Focus**: Research focus areas (Metals, Steel, 3D printing, Meta materials, etc.)


## 🎨 Styling

- **Primary Color**: `#7fabc9`
- **Framework**: Tailwind CSS v4
- **Theme**: Light mode only
- **Typography**: Inter font family

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📧 Contact

For questions about the ME Projects Portal or project inquiries, please visit the [contact page](/contact) or reach out to the Department of Mechanical Engineering at Eindhoven University of Technology. Repo owner can be contacted for technical matters: @Rozenlicht. (Bart Verhaegh)

---

**Built with ❤️ for the Department of Mechanical Engineering at Eindhoven University of Technology**
