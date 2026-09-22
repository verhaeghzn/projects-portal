<?php

namespace App\Onboarding;

use App\Filament\Pages\AssignProjects;
use App\Filament\Pages\GroupMembers;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Str;

class WelcomeGuide
{
    public const ADMIN_BOOKMARK_URL = 'https://studentprojects.wtb.tue.nl/admin';

    public function __construct(private User $user) {}

    public static function for(User $user): self
    {
        return new self($user);
    }

    public function firstName(): string
    {
        $first = Str::of($this->user->name)->trim()->explode(' ')->first();

        return $first !== '' ? $first : $this->user->name;
    }

    public function roleHeading(): string
    {
        return match (true) {
            $this->user->hasRole('Administrator') => 'administrator',
            $this->user->hasRole('Support colleague') => 'support colleague',
            $this->user->hasRole('Staff member - supervisor') => 'staff member',
            $this->user->hasRole('Researcher') => 'researcher',
            default => 'colleague',
        };
    }

    public function intro(): string
    {
        $group = $this->user->group?->name;

        return match (true) {
            $this->user->hasRole('Staff member - supervisor') => $group
                ? "Your staff account for {$group} is ready. This portal is where Mechanical Engineering publishes bachelor and master thesis projects for students — and where you manage the projects and people in your group."
                : 'Your staff account is ready. This portal is where Mechanical Engineering publishes bachelor and master thesis projects for students — and where you manage the projects you own.',
            $this->user->hasRole('Researcher') => $group
                ? "Your researcher account for {$group} is ready. You can publish thesis projects for students, keep the ones you supervise up to date, and see how they appear on the public portal."
                : 'Your researcher account is ready. You can publish thesis projects for students, keep the ones you supervise up to date, and see how they appear on the public portal.',
            $this->user->hasRole('Support colleague') => 'Your support account is ready. You can help your division keep the project catalogue complete: assign owners, update listings, and export the overview your section uses.',
            $this->user->hasRole('Administrator') => 'Your administrator account is ready. You have full access to projects, users, and the department structure behind the public portal.',
            default => 'Your account is ready. Here is a short tour of the portal and the tools you can use.',
        };
    }

    /**
     * @return list<array{title: string, body: list<string>, image: string|null, imageAlt: string, caption: string|null, url?: string}>
     */
    public function features(): array
    {
        $features = [
            $this->bookmarkFeature(),
            $this->workspaceFeature(),
        ];

        if ($this->user->can('create', Project::class)) {
            $features[] = $this->createProjectFeature();
        }

        if ($this->user->can('viewAny', Project::class)) {
            $features[] = $this->manageProjectsFeature();
        }

        if ($this->canInviteGroupMembers()) {
            $features[] = $this->groupMembersFeature();
        }

        if ($this->canAssignProjects()) {
            $features[] = $this->assignProjectsFeature();
        }

        return $features;
    }

    public function canLinkTueAccount(): bool
    {
        return blank($this->user->surf_id);
    }

    private function bookmarkFeature(): array
    {
        return [
            'title' => 'Bookmark the admin panel',
            'body' => [
                'The address below is the staff entrance. Bookmark it so you can open the admin panel in one click, without going through the public student site.',
                'On a Mac, open the link and press Cmd+D. On Windows, press Ctrl+D. You can also copy the address and save it in your browser bookmarks.',
            ],
            'image' => null,
            'imageAlt' => '',
            'caption' => null,
            'url' => self::ADMIN_BOOKMARK_URL,
        ];
    }

    private function workspaceFeature(): array
    {
        $items = [
            'The dashboard is your workspace. It shows how many projects are available and lists recent activity.',
            'Use the menu on the left for Projects, Organizations, and Help & Contact. Your name in the top-right opens your profile, where you can change your photo or password.',
        ];

        if ($this->user->can('export division projects') && $this->user->group?->section?->division) {
            $items[] = 'Your dashboard also includes an export for your division, which sections use when they divide incoming student interest.';
        }

        return [
            'title' => 'Your workspace',
            'body' => $items,
            'image' => 'assets/images/onboarding/dashboard.png',
            'imageAlt' => 'The admin dashboard with project statistics and recent projects',
            'caption' => 'The dashboard is the first screen of the admin panel.',
        ];
    }

    private function createProjectFeature(): array
    {
        $body = $this->user->hasRole('Researcher')
            ? [
                'Open Projects and choose New to offer a bachelor or master thesis project. Add a clear title, the project type, a short description for the listing card, and the full text students will read.',
                'The project owner and the first supervisor must be a TU/e staff member — usually your group leader. You can add yourself as an extra supervisor so you can keep editing the page.',
                'Leave Published on when the project is ready for students. Turn it off to keep a draft until the description is finished.',
            ]
            : [
                'Open Projects and choose New to offer a bachelor or master thesis project. Add a clear title, the project type, a short description for the listing card, and the full text students will read.',
                'You are typically the project owner and first supervisor. You can add researchers from your group, or an external supervisor, as extra supervisors.',
                'Leave Published on when the project is ready for students. Turn it off to keep a draft until the description is finished.',
            ];

        return [
            'title' => 'Publish a project for students',
            'body' => $body,
            'image' => 'assets/images/onboarding/create-project.png',
            'imageAlt' => 'The form used to create a new thesis project',
            'caption' => 'A project needs a title, type, owner, description, and at least one staff supervisor.',
        ];
    }

    private function manageProjectsFeature(): array
    {
        $body = match (true) {
            $this->user->hasRole('Staff member - supervisor') => [
                'You can edit projects you own, and projects whose supervisors belong to a group you lead. You can delete a project only when you are its owner.',
                'When a student starts, open the project and fill in their name and email. Once the project has finished and you mark it as public, it can appear on the Past Projects list.',
            ],
            $this->user->hasRole('Researcher') => [
                'You can edit projects you created or supervise, and delete a project only when you created it.',
                'When a student starts, open the project and fill in their name and email. Once the project has finished and you mark it as public, it can appear on the Past Projects list.',
            ],
            $this->user->hasRole('Support colleague') => [
                'You can update or remove projects that belong to your division, including listings created by others, so the catalogue stays accurate.',
                'When a student has started, the project can store their name and email. Public finished projects appear on the Past Projects list.',
            ],
            default => [
                'Open Projects to search, edit, or unpublish listings. When a student starts, add their details on the project page.',
                'Finished public projects appear on the Past Projects list that visitors can browse without signing in.',
            ],
        };

        return [
            'title' => 'Keep projects up to date',
            'body' => $body,
            'image' => 'assets/images/onboarding/projects.png',
            'imageAlt' => 'The projects table in the admin panel',
            'caption' => 'The Projects page lists every listing you are allowed to open.',
        ];
    }

    private function groupMembersFeature(): array
    {
        return [
            'title' => 'Invite people to your group',
            'body' => [
                'Group Members shows everyone in your group. Use Invite new user to add a researcher or staff colleague — they receive an email and set up their account.',
                'Invited colleagues are attached to your group automatically, so their projects stay with the right section.',
            ],
            'image' => 'assets/images/onboarding/group-members.png',
            'imageAlt' => 'The Group Members page listing colleagues in a research group',
            'caption' => 'Group Members is the people list for your group.',
        ];
    }

    private function assignProjectsFeature(): array
    {
        return [
            'title' => 'Assign projects in your division',
            'body' => [
                'Assign projects is the overview your division uses when students have applied and projects need an owner or supervisor.',
                'You can update those assignments without opening every project form individually.',
            ],
            'image' => 'assets/images/onboarding/projects.png',
            'imageAlt' => 'The projects table used when assigning owners and supervisors',
            'caption' => 'Assign projects lives under Projects in the left menu.',
        ];
    }

    private function canInviteGroupMembers(): bool
    {
        return $this->user->group_id !== null
            && $this->user->hasAnyRole(['Administrator', 'Staff member - supervisor'])
            && GroupMembers::canAccess();
    }

    private function canAssignProjects(): bool
    {
        return AssignProjects::canAccess();
    }
}
